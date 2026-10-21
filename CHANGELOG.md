## CHANGELOG

Authors: See the [composer.json authors block](https://github.com/icepay/API/blob/master/composer.json) for all the authors and contributors of this project.

### Unreleased

Bugfixes:

- Declare the client's payment and refund resources to prevent dynamic-property deprecations on PHP 8.2 and PHP 8.3 without raising the minimum PHP version.
- Add offline compatibility checks for client initialization and payment/refund request generation.

### 0.0.3 (27-12-2020)

Features:

  - Add support for paysafecard MerchantClientID in Payment->checkOut


### 0.0.2 (18-04-2016)

Bugfixes:

  - Fixed vulnerability that made a man-in-the-middle attack possible.
  - Determine client IP from SERVER variables. The function 'getenv' does not work if your Server API is ASAPI (IIS).


### 0.0.1 (29-09-2015)

Features:

  - Initial public release for internal testing purposes.
  - GitHub markdown style documentation about the rest api.

Bugfixes:

  - Included intermediate certificates for curl error 60 or 77.
