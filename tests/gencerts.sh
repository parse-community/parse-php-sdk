#!/bin/bash
# https://gist.github.com/ryankurte/bc0d8cff6e73a6bb1950
# ./gencerts.sh parseca localhost parsephp keys/
# ./gencerts.sh parseca client parsephp keys/

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
DAYS=1000

SUBJECT=/C=NZ/ST=AKL/L=Auckland/O=$ORG

if [ ! -f "$DIR/$CA.key" ]; then

  echo Generating CA
  openssl genrsa -out $DIR/$CA.key $LENGTH

  echo Signing CA
  openssl req -x509 -new -nodes -key $DIR/$CA.key -sha256 -days 1024 -out $DIR/$CA.crt -subj $SUBJECT/CN=$CA

  openssl x509 -in $DIR/$CA.crt -out $DIR/$CA.pem -text
  openssl x509 -sha1 -noout -in $DIR/$CA.pem -fingerprint | sed 's/SHA1 Fingerprint=//g' >> $DIR/$CA.fp

else
  echo Located existing CA
fi

if [ ! -f "$DIR/$NAME.key" ]; then

echo Generating keys
openssl genrsa -out $DIR/$NAME.key $LENGTH

echo Generating CSR
openssl req -new -out $DIR/$NAME.csr -key $DIR/$NAME.key -subj $SUBJECT/CN=$NAME

echo Signing cert
openssl x509 -req -days $DAYS -in $DIR/$NAME.csr -out $DIR/$NAME.crt -CA $DIR/$CA.crt -CAkey $DIR/$CA.key -CAcreateserial

echo Generating PEM
openssl x509 -in $DIR/$NAME.crt -out $DIR/$NAME.pem -text

openssl x509 -sha1 -noout -in $DIR/$NAME.pem -fingerprint | sed 's/SHA1 Fingerprint=//g' > $DIR/$NAME.fp

echo Cleaning Up
rm $DIR/*.csr

else
  echo Located existing client certificate
fi

echo Done
