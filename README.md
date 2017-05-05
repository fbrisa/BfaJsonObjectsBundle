# BfaJsonObjectBundle


## Requirements
none

## Installation

Add to composer.json:
```
"require" : {
    "bfa/jsonobjectsbundle": "^1.0"
},
```

```
"repositories" : [{
    "type" : "vcs",
    "url" : "https://github.com/fbrisa/BfaJsonObjectsBundle.git"
}]
```

Add to you app/AppKernel.php:
```php
new Bfa\JsonObjectsBundle\BfaJsonObjectsBundle(),
```

Add route to app/routing.tml

```yml
bfa_jsonobjects:
    resource: "@BfaJsonObjectsBundle/Resources/config/routing.yml"
    prefix:   /
```


## Examples:
See doc folder for examples
    