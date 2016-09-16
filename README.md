# README #

Yii2 Parser by domain

### By this package you can: ###

* grab all links of domain
* store all *.pdf files (or all extensions which you need)

### Installation: ###

add to file composer.json:

```
#!php

"require": {
   "maxlen/yii2_parse_by_domain" : "dev-master"
},
"repositories": [
   {
      "type": "git",
      "url": "https://github.com/maxlen/yii2_parse_by_domain.git"
   },
]...
```

OR In console:

```
#!php

php composer.phar require maxlen/yii2_parse_by_domain
```


### How should I use it: ###

```
#!php
use maxlen\emailvalidator\helpers\EmailValidator;

EmailValidator::validate('spiderman@superbot.com', $params));
EmailValidator::emailsFromString($string, $params));

, where:
$params = [
   'exceptions' => ['ask.', 'linked', '.ru'],
   'shouldBeNotFreeProvider' => true,
   'domainExists' => true,
];
$string - any string (for example: html-code. You can get emails from web-page)

EmailValidator::domainExists($email, $record); // returns boolean

, where:
$record - MX, A, ...

EmailValidator::isFreeEmailProvider($email); // returns boolean
```

etc.
