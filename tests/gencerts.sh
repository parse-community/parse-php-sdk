#!/bin/bash
# https://gist.github.com/ryankurte/bc0d8cff6e73a6bb1950
# https://curl.se/libcurl/c/CURLOPT_PINNEDPUBLICKEY.html
# ./gencerts.sh parseca localhost parsephp keys/
# ./gencerts.sh parseca client parsephp keys/
#
# Existing private keys are always reused, so the public key hash pinned by the
# test suite through CURLOPT_PINNEDPUBLICKEY stays valid. Set RESIGN=1 to issue
# fresh certificates from those keys, which is how expired certificates are
# renewed:
#
# RESIGN=1 ./gencerts.sh parseca localhost parsephp tests/keys/
# RESIGN=1 ./gencerts.sh parseca client parsephp tests/keys/
#
# The SHA1 fingerprint of localhost.crt is pinned as `peer_fingerprint` in
# tests/Parse/ParseClientTest.php and must be updated after a re-signing:
#
# openssl x509 -in tests/keys/localhost.crt -noout -fingerprint -sha1

set -e

if [ "$#" -ne 3 ] && [ "$#" -ne 4 ]; then 
  echo "Usage: $0 CA NAME ORG"
  echo "CA - name of fake CA"
  echo "NAME - name of fake client"
  echo "ORG - organisation for both"
  echo "[DIR] - directory for cert output"
  exit
fi

CA=$1
NAME=$2
ORG=$3

if [ -z "$4" ]; then
  DIR=./
else
  DIR=$4
fi

if [ ! -d "$DIR" ]; then
  mkdir -p $DIR
fi

LENGTH=4096
DAYS=7300

SUBJECT=/C=NZ/ST=AKL/L=Auckland/O=$ORG

# Node >= 18 and OpenSSL 3 no longer fall back to the common name, so the
# certificates need proper X509v3 extensions.
EXTFILE=$(mktemp)
trap 'rm -f $EXTFILE' EXIT

if [ "$NAME" = "localhost" ]; then
  cat > $EXTFILE <<EOF
basicConstraints = CA:FALSE
keyUsage = critical, digitalSignature, keyEncipherment
extendedKeyUsage = serverAuth
subjectAltName = DNS:localhost, IP:127.0.0.1
EOF
else
  cat > $EXTFILE <<EOF
basicConstraints = CA:FALSE
keyUsage = critical, digitalSignature, keyEncipherment
extendedKeyUsage = clientAuth
subjectAltName = DNS:$NAME
EOF
fi

if [ ! -f "$DIR/$CA.key" ]; then
  echo Generating CA key
  openssl genrsa -out $DIR/$CA.key $LENGTH
else
  echo Located existing CA key
fi

if [ ! -f "$DIR/$CA.crt" ] || [ -n "$RESIGN" ]; then
  echo Signing CA
  openssl req -x509 -new -nodes -key $DIR/$CA.key -sha256 -days $DAYS \
    -out $DIR/$CA.crt -subj $SUBJECT/CN=$CA \
    -addext "basicConstraints = critical, CA:TRUE" \
    -addext "keyUsage = critical, keyCertSign, cRLSign"

  openssl x509 -in $DIR/$CA.crt -out $DIR/$CA.pem
  openssl x509 -sha1 -noout -in $DIR/$CA.pem -fingerprint | sed 's/.*Fingerprint=//g' > $DIR/$CA.fp
else
  echo Located existing CA certificate
fi

if [ ! -f "$DIR/$NAME.key" ]; then
  echo Generating keys
  openssl genrsa -out $DIR/$NAME.key $LENGTH
else
  echo Located existing key
fi

if [ ! -f "$DIR/$NAME.crt" ] || [ -n "$RESIGN" ]; then
  echo Generating CSR
  openssl req -new -out $DIR/$NAME.csr -key $DIR/$NAME.key -subj $SUBJECT/CN=$NAME

  echo Signing cert
  openssl x509 -req -days $DAYS -sha256 -in $DIR/$NAME.csr -out $DIR/$NAME.crt \
    -CA $DIR/$CA.crt -CAkey $DIR/$CA.key -CAcreateserial -extfile $EXTFILE

  echo Generating PEM
  openssl x509 -in $DIR/$NAME.crt -out $DIR/$NAME.pem
  openssl x509 -in $DIR/$NAME.crt -outform der -out $DIR/$NAME.der
  openssl x509 -in $DIR/$NAME.crt -noout -pubkey > $DIR/$NAME.pubkey.pem
  openssl pkey -pubin -in $DIR/$NAME.pubkey.pem -outform der -out $DIR/$NAME.pubkey.der

  openssl x509 -sha1 -noout -in $DIR/$NAME.pem -fingerprint | sed 's/.*Fingerprint=//g' > $DIR/$NAME.fp

  echo Cleaning Up
  rm -f $DIR/$NAME.csr
else
  echo Located existing client certificate
fi

echo Done
