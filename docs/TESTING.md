# Local smoke tests
Run from the repository root after PHP 8.3+ and required extensions are installed:
```bash
php tests/smoke_check.php
php tests/run.php
find . -type f -name '*.php' -not -path './vendor/*' -print0 | xargs -0 -n1 php -l
```
The smoke tests validate runtime extensions and URL validation/platform mapping. Provider integration tests require valid API credentials and should be run with a small test audio file to avoid unexpected usage charges.
