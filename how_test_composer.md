Что бы добавить библиотеку в проект
composer.json => 

    "repositories": [
        {
            "type": "path",
            "url": "../plugin_auth/adideas/oauth_server/"
        }
    ],

Что бы установить

composer require "adideas/oauth_server"

Может не отработать тогда заходим в вендор и там заменяем ссылку на 

mklink /d

готово!