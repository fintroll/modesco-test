Для развёртывния необходимо:
1) клонировать репозиторий в нужную папку
2) Выполнить `composer install` для установки компонентов
3) Выполнить `composer dump -o` для генерации autoload

Команды для использования краулера:
1) `php crawler list` - показывает список всех доступных команд
2) `php crawler help <команда>` - описание и список параметров для запуска команды
3) `php crawler parse <базовый url сайта> <глубина парсинга>` - запуск паука

Параметры: 

<базовый url сайта> (string) - обязательный параметр.   
Если не указан - скрипт завершится с ошибкой `Not enough arguments (missing: "url")`.   
Если невалидный url  - скрипт завершится с ошибкой `Entered url: <базовый url сайта> is not valid. Stopping...`   
Если неверный url  - скрипт завершится с ошибкой `Parsing links completed. Found 0 links. Check base url`   

<глубина парсинга> (integer) - необязательный параметр.  
Если не указан, или указано не число - будет использовано значение по умолчанию, равное 1.  
Влияет на время выполнения команды.
