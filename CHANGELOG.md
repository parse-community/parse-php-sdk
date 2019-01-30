## Parse PHP SDK Changelog

### master
[Full Changelog](https://github.com/parse-community/parse-php-sdk/compare/1.5.1...master)

### 1.5.1
[See the diff between 1.5.0 and 1.5.1](https://github.com/parse-community/parse-php-sdk/compare/1.5.0...1.5.1)

No changes from  1.5.0.  Creating release to fix mis deployed 1.5.0.

### 1.5.0
[See the diff between 1.4.0 and 1.5.0](https://github.com/parse-community/parse-php-sdk/compare/1.4.0...1.5.0)

- Avoid session fixation by regenerating session id on user promotion (#414)
- Enable login with POST (#430)
- Properly handle null query response (#425)
- Fix queries equalTo with null values (#406)
- Add sort option to GeoQueries (#424)
- Add encode option to ParseQuery:find (#423)
- Add batchSize to saveAll / destroyAll (#422)
- Add includeAll to query (#421)
- Add And / Nor query (#419)
- Add 'containedBy' query (#418)
- Add 'containsAllStartingWith' query (#417)
- Remove anonymous auth data from User table when user registers.  Match other SDK behavior (#417)
- Fix bug in mime type guessing due to case mishandling (#399)
- Use registered subclass when creating a ParseUser (#394)


### 1.4.0
[See the diff between 1.3.0 and 1.4.0](https://github.com/parse-community/parse-php-sdk/compare/1.3.0...1.4.0)

- Fixes encode/decode method descriptors
- Adds Relative Time Queries (#360)
- Adds Server Info (#361)
- README and code cleanup, adds **CHANGELOG** and **CODE_OF_CONDUCT**
- Adds Purge & Polygon to ParseSchema (#365)
- Adds Parse Server Health Check (#366)
- Adds the ability to upgrade to a revocable session (#368)
- Adds ability to Request Verification Emails (#369)
- Adds the ability to set/save in `ParseConfig` (#371)
- Adds `ParseLogs` (#370)
- Adds `ParseAudience` (#372)
- Adds jobs to `ParseCloud` (#373)
- Adds support for aggregate queries (#355) (thanks to [Diamond Lewis](https://github.com/dplewis))
- Fix npm license warning (thanks to [Arthur Cinader](https://github.com/acinader))
- Updates **parse-server-test** dependency to 1.3.6
- Support for managing indexes via **ParseSchema** (#357) (thanks to [Diamond Lewis](https://github.com/dplewis))
- Slight test adjustments

### 1.3.0
[See the diff between 1.2.10 and 1.3.0](https://github.com/parse-community/parse-php-sdk/compare/1.2.10...1.3.0)

- Adds **HHVM** support
- Modified `ParseFile` to use the current **HttpClient** rather than just curl for download
- Adds full text search via `ParseQuery::fullText` for Parse Server **2.5.0** and later (thanks to [Diamond Lewis](https://github.com/dplewis))
- Adds **encode**/**decode** support to `ParseObject`
- Travis CI cache fixes
- Slight test modifications for later versions of parse
- A few README typo fixes and additions

### 1.2.10
[See the diff between 1.2.9 and 1.2.10](https://github.com/parse-community/parse-php-sdk/compare/1.2.9...1.2.10)

- Updates to make the sdk friendly with `phpdoc`
- Added **Getting Started** section to README
- Removed the default server and mount path for `api.parse.com`
- Setup `phpdoc` style enforcing and autodeploy from most recent `master` for our [api ref](http://parseplatform.org/parse-php-sdk/namespaces/Parse.html)
- **jms/serializer** pinned to **1.7.1** for testing as mentioned in #336 (for phpdoc)
- Added **ParsePolygon** type and `polygonContains` to **ParseQuery** (thanks to [Diamond Lewis](https://github.com/dplewis))
- Enhanced numerious exceptions with proper error codes, following the [guide error codes](http://docs.parseplatform.org/php/guide/#error-codes)
- CI improvements and expanded to run tests under `ParseStreamClient`

### 1.2.9
[See the diff between 1.2.8 and 1.2.9](https://github.com/parse-community/parse-php-sdk/compare/1.2.8...1.2.9)

- Integrates ParseServer for testing the sdk against, for convenience
- Patch for associative arrays properly being encoded in ParseObject `_encode`
- Adds `matches` to ParseQuery
- Adds Travis CI for running tests on PRs as they are submitted
- Adds `withinPolygon` to ParseQuery
- Updates php_codesniffer and enforces [PSR-2 Coding Style](http://www.php-fig.org/psr/psr-2/) on the sdk

### 1.2.8
[See the diff between 1.2.7 and 1.2.8](https://github.com/parse-community/parse-php-sdk/compare/1.2.7...1.2.8)

- General test improvements (thanks to [Ben Friedman](https://github.com/montymxb))
- Update composer to point to parse-community (thanks to [Arthur Cinader](https://github.com/acinader))
- Fix to save ParseFiles properly when saving ParseObject (thanks to [Damien Matabon](https://github.com/zeliard91))

### 1.2.7
[See the diff between 1.2.6 and 1.2.7](https://github.com/parse-community/parse-php-sdk/compare/1.2.6...1.2.7)

- Doc links updated (thanks to [Ben Friedman](https://github.com/montymxb))
- Adds getters for ParseInstallation (thanks to [Ben Friedman](https://github.com/montymxb))
- Improvements to checking status via ParsePushStatus (thanks to [Ben Friedman](https://github.com/montymxb))

### 1.2.6
[See the diff between 1.2.5 and 1.2.6](https://github.com/parse-community/parse-php-sdk/compare/1.2.5...1.2.6)

- Notice of code transfer to parse-community by [Kevin Lacker](https://github.com/lacker)
- Always use '&' instead of relying on ini for query strings (thanks to [Arthur Cinader](https://github.com/acinader))

### 1.2.5

[See the diff between 1.2.4 and 1.2.5](https://github.com/parse-community/parse-php-sdk/compare/1.2.4...1.2.5)

- Adds Twitter login helper (thanks to [Veri Ferdiansyah](https://github.com/vferdiansyah))

### 1.2.4

[See the diff between 1.2.3 and 1.2.4](https://github.com/parse-community/parse-php-sdk/compare/1.2.3...1.2.4)

- Add `contains` to ParseQuery (thanks to [Arthur Cinader](https://github.com/acinader))
- Fix for bi directional relations being saved when an array of pointers is used and is in dirty state (thanks to [Ben Friedman](https://github.com/montymxb))
- Adds switchable http clients (transport layers) with configuration options (thanks to [Ben Friedman](https://github.com/montymxb))

### 1.2.3
[See the diff between 1.2.2 and 1.2.3](https://github.com/parse-community/parse-php-sdk/compare/1.2.2...1.2.3)

- Updates ParseQuery to use `regex` internally for `startsWith` and `endsWith` (thanks to [evaldas-leliuga](https://github.com/evaldas-leliuga))
- Updated with requirement of 'where' or 'query' in ParsePush (thanks to [Ben Friedman](https://github.com/montymxb))
- Added some missing docs (thanks to Alaa Attya)

### 1.2.2
[See the diff between 1.2.1 and 1.2.2](https://github.com/parse-community/parse-php-sdk/compare/1.2.1...1.2.2)

- Fix for checking if the mount path is root (thanks to [Ben Friedman](https://github.com/montymxb))
- Fix for @docs annotation issue (thanks to [Paul Inman](https://github.com/inmanpaul))
- Added check and conversion to string from Array for 'error' in back4app.com API response (thanks to [Ben Friedman](https://github.com/montymxb))
- Standardize upload and delete methods of ParseFile to use ParseClient::_request (thanks to [Ben Friedman](https://github.com/montymxb))
- Made REST API key optional (thanks to [Ben Friedman](https://github.com/montymxb))
- Added the ability to get headers in response to sending a push (thanks to [Stas Kuryan](https://github.com/Stafox))
- Updated Tests & SDK for Open Source Parse Server (thanks to [Ben Friedman](https://github.com/montymxb))

### 1.2.1
[See the diff between 1.2.0 and 1.2.1](https://github.com/parse-community/parse-php-sdk/compare/1.2.0...1.2.1)

- Added float casting on lat/lng in ParseGeoPoint (thanks to [William George](https://github.com/awgeorge))
- Fix: Screen for whether the 'objects' field is set under add or remove relations (thanks to [Ben Friedman](https://github.com/montymxb))
- Fix: Removed appended version number causing batch requests to fail (thanks to [Ben Friedman](https://github.com/montymxb))
- Separated serverURL & mountPath params in `setServerURL` and fixed batch ops behavior (thanks to [Ben Friedman](https://github.com/montymxb))
- Added `endsWith` method to `ParseQuery` (thanks to [Abhinav Kumar](https://github.com/abhinavkumar940))
- Added ability to trigger `beforeSave` method (thanks to [William George](https://github.com/awgeorge))
- Fixed: #238 Pointers now save without fetching (thanks to [William George](https://github.com/awgeorge))
- Fix for incorrectly formatted UUID (thanks to [Andrew Yates](https://github.com/ay8s))

### 1.2.0
[See the diff between 1.1.10 and 1.2.0](https://github.com/parse-community/parse-php-sdk/compare/1.1.10...1.2.0)

- Updated to allow changing the server URL for the open source parse-server (thanks to [Fosco Marotto](https://github.com/gfosco))

### 1.1.10
[See the diff between 1.1.9 and 1.1.10](https://github.com/parse-community/parse-php-sdk/compare/1.1.9...1.1.10)

- Added `ParseApp` (thanks to [Phelipe Alves de Souza](https://github.com/phelipealves))
- Fixed serialization of unindexed arrays (thanks to [Damien Matabon](https://github.com/zeliard91))
- Added `getAllKeys` to `ParseObject` (thanks to [Mayank Gandhi](https://github.com/mistahgandhi))

### 1.1.9
[See the diff between 1.1.8 and 1.1.9](https://github.com/parse-community/parse-php-sdk/compare/1.1.8...1.1.9)

- General enhancements to ParseSchema (thanks to [Phelipe Alves de Souza](https://github.com/phelipealves))
- Added `ParseHooks` (thanks to [Phelipe Alves de Souza](https://github.com/phelipealves))

### 1.1.8
[See the diff between 1.1.7 and 1.1.8](https://github.com/parse-community/parse-php-sdk/compare/1.1.7...1.1.8)

- Changes for PSR2 possible compliance (thanks to [Julián Gutiérrez](https://github.com/juliangut))
- PHPdoc and code quality improvements (thanks to [Phelipe Alves de Souza](https://github.com/phelipealves))
- Batch operations fix (thanks to [Phelipe Alves de Souza](https://github.com/phelipealves))
- Added `ParseSchema` (thanks to [Júlio César Gonçalves de Oliveira](https://github.com/pinguineras))

### 1.1.7
[See the diff between 1.1.6 and 1.1.7](https://github.com/parse-community/parse-php-sdk/compare/1.1.6...1.1.7)

- Support empty query in `ParsePush` (thanks to [Sahan H.](https://github.com/sahanh))
- Added missing PSR4 update on CONTRIBUTING.md (thanks to [Ivan](https://github.com/stoiev))
- Expose timeout parameters in `ParseClient` (thanks to [Ivan](https://github.com/stoiev))
- Added missing 2nd parameter in call to `ParseClient::_request` (thanks to [Phelipe Alves de Souza](https://github.com/phelipealves))
- Added missing throws tags for PHPDoc (thanks to [Phelipe Alves de Souza](https://github.com/phelipealves))
- Remove import of `Exception` in `ParseRole` (thanks to [Phelipe Alves de Souza](https://github.com/phelipealves))
- Add optional `className` to `ParseObject::getRelation` (thanks to [Fosco Marotto](https://github.com/gfosco))

### 1.1.6
[See the diff between 1.1.5 and 1.1.6](https://github.com/parse-community/parse-php-sdk/compare/1.1.5...1.1.6)

- Use `ParseObject::create` to create new ParseUser (thanks to [Ben Flannery](https://github.com/oflannabhra))
- Better api url construction (thanks to [Julián Gutiérrez](https://github.com/juliangut))
- Tests PSR4 autoloading (thanks to [Julián Gutiérrez](https://github.com/juliangut))
- Remove API version constant from `ParseClient::_request` calls (thanks to [Julián Gutiérrez](https://github.com/juliangut))
- Improved API url generation (thanks to [Julián Gutiérrez](https://github.com/juliangut))
- StyleCI fixes (thanks to [Yaman Jain](https://github.com/yaman-jain))
- Fix for ParsePush with ParseQuery which contains ParseObject does not work (thanks to [Julián Gutiérrez](https://github.com/juliangut))

### 1.1.5
[See the diff between 1.1.4 and 1.1.5](https://github.com/parse-community/parse-php-sdk/compare/1.1.4...1.1.5)

- Updated `ParseObject::fetch` to return itself (thanks to [William George](https://github.com/awgeorge))
- Added `loginWithAnonymous` and a couple bug fixes (thanks to [Honghao Liang](https://github.com/fcrosfly))
- Fix unable to get zero or false value without an issue (thanks to yutaro-ihara)

### 1.1.4
[See the diff between 1.1.3 and 1.1.4](https://github.com/parse-community/parse-php-sdk/compare/1.1.3...1.1.4)

- Fixes for Facebook login (thanks to [Fosco Marotto](https://github.com/gfosco))
- Updated push handling for local or non-local time push (thanks to [Fosco Marotto](https://github.com/gfosco))

### 1.1.3
[See the diff between 1.1.2 and 1.1.3](https://github.com/parse-community/parse-php-sdk/compare/1.1.2...1.1.3)

- Updated README to fix guide link (thanks to [Héctor Ramos](https://github.com/hramos))
- Made `ParseInstallation` capable of being subclassed (thanks to [Koichi Yamamoto](https://github.com/noughts))
- Fix destroyAll with useMasterKey option (thanks to [Koichi Yamamoto](https://github.com/noughts))
- Added missing backslash to DateTime usage (thanks to [Fosco Marotto](https://github.com/gfosco))
- Added isset handler to ParseObject (thanks to [Fosco Marotto](https://github.com/gfosco))
- Updated array processing in destroyAll (thanks to [Fosco Marotto](https://github.com/gfosco))
- Added encoding support for DateTimeImmutable (thanks to [Fosco Marotto](https://github.com/gfosco))

### 1.1.2
[See the diff between 1.1.1 and 1.1.2](https://github.com/parse-community/parse-php-sdk/compare/1.1.1...1.1.2)

- Made ParseUser and ParseRole subclassable (thanks to [Caleb Fidecaro](https://github.com/HipsterJazzbo))
- Added login/link with Facebook support (thanks to [Fosco Marotto](https://github.com/gfosco))
- Added `ParseObject::fetchAll` (thanks to [Fosco Marotto](https://github.com/gfosco))
- Removed wrong use lines inserted by editor (thanks to [Fosco Marotto](https://github.com/gfosco))

### 1.1.1
[See the diff between 1.1.0 and 1.1.1](https://github.com/parse-community/parse-php-sdk/compare/1.1.0...1.1.1)

- Updated for full compliance with StyleCI (thanks to [Fosco Marotto](https://github.com/gfosco) && [Graham Campbell](https://github.com/GrahamCampbell))
- Added sessionToken to `ParseQuery::count` (thanks to [Fosco Marotto](https://github.com/gfosco))

### 1.1.0
[See the diff between 1.0.6 and 1.1.0](https://github.com/parse-community/parse-php-sdk/compare/1.0.6...1.1.0)

- Added `ParseSession` (thanks to [Fosco Marotto](https://github.com/gfosco))

### 1.0.6
[See the diff between 1.0.5 and 1.0.6](https://github.com/parse-community/parse-php-sdk/compare/1.0.5...1.0.6)

- Added `ParseConfig` (thanks to [Fosco Marotto](https://github.com/gfosco))
- Added `use Parse\ParseClient;` in README (thanks to [Kevin T'Syen](https://github.com/NoScopie))

### 1.0.5
[See the diff between 1.0.4 and 1.0.5](https://github.com/parse-community/parse-php-sdk/compare/1.0.4...1.0.5)

- Added minimum PHP version (5.4) to readme (thanks to [Fosco Marotto](https://github.com/gfosco))
- Modify `ParseQuery::includeKey` to return itself (thanks to [Somasundaram Ayyappan](https://github.com/somus))
- Added option to enable/disable curl exceptions (thanks to [Luciano Nascimento](https://github.com/lucianocn))
- Added useMasterKey param to `ParseObject::fetch` (thanks to [Fosco Marotto](https://github.com/gfosco))

### 1.0.4
[See the diff between 1.0.3 and 1.0.4](https://github.com/parse-community/parse-php-sdk/compare/1.0.3...1.0.4)

- Fix DocBlock statements for better compatibility with Annotation libraries (thanks to [Schuyler Jager](https://github.com/schuylr))
- Fix ParseAnalytics usage in README (thanks to [Cihad ÖGE](https://github.com/cihadoge))
- Remove autogenerated OSX files (thanks to [Schuyler Jager](https://github.com/schuylr))
- Fix for #31 - encode each value from associative array (thanks to [Schuyler Jager](https://github.com/schuylr))
- More specific Exceptions in `ParseObject::__construct` (thanks to [Schuyler Jager](https://github.com/schuylr))
- Added a message in the construct exception in case developer forgets to call `ParseClient::initialize` (thanks to [Schuyler Jager](https://github.com/schuylr))

### 1.0.3
[See the diff between 1.0.2 and 1.0.3](https://github.com/parse-community/parse-php-sdk/compare/1.0.2...1.0.3)

- Made properties inside ParseObject that implement \Parse\Internal\Encodable to be encodable (thanks to [Osniel Gonzalez](https://github.com/osniel))
- Fix datetime format issue in Local Push Scheduling (thanks to Frank He)

### 1.0.2
[See the diff between 1.0.1 and 1.0.2](https://github.com/parse-community/parse-php-sdk/compare/1.0.1...1.0.2)

- Fix issue with ACL & role (thanks to Mathieu Moriceau)
- Pass useMasterKey to deepSave on saveAll (thanks to [Eric Green](https://github.com/egreenmachine))
- Allow saving Parse Objects with Master Key (thanks to [Eric Green](https://github.com/egreenmachine))
- Fix bug saving using MasterKey (thanks to [Eric Green](https://github.com/egreenmachine))
- Added delete to ParseFile (thanks to [Fosco Marotto](https://github.com/gfosco))
- Matching save signature on ParseUser & ParseRole (thanks to [Fosco Marotto](https://github.com/gfosco))

### 1.0.1
[See the diff between 1.0.0 and 1.0.1](https://github.com/parse-community/parse-php-sdk/compare/1.0.0...1.0.1)

- Added syntax highlighting to README (thanks to [Koen Schmeets](https://github.com/vespakoen))
- Added autoload for those that don't want to use Composer (thanks to [Niraj Shah](https://github.com/niraj-shah))
- Updated path for `PARSE_SDK_DIR` (thanks to [Niraj Shah](https://github.com/niraj-shah))
- Added use of current user session in `ParseCloud::run` (thanks to [Niraj Shah](https://github.com/niraj-shah))
- Fix for `where` parameter in `ParsePush::send` (thanks to [Niraj Shah](https://github.com/niraj-shah))
- Added init instructions to README (thanks to [Niraj Shah](https://github.com/niraj-shah))
- Updated composer.json dependencies (thanks to [Graham Campbell](https://github.com/GrahamCampbell))
- Added a branch alias (thanks to [Graham Campbell](https://github.com/GrahamCampbell))
- Updated visibility of `ParseObject::_isDirty` to `protected` (thanks to [Fosco Marotto](https://github.com/gfosco))

### 1.0.0
- Initial release! (thanks to [Fosco Marotto](https://github.com/gfosco))
