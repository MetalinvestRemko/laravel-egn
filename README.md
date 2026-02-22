# Laravel EGN

Laravel пакет за:

- валидиране на българско ЕГН;
- генериране на валидно ЕГН по незадължителни критерии:
  - пол (`male|female|m|f`);
  - дата на раждане (`date` или частично `year`, `month`, `day`);
  - регион (`0..99`);
  - брой генерирани ЕГН.

## Инсталация

```bash
composer require ivajlokostov/laravel-egn
```

Публикуване на конфигурацията (по желание):

```bash
php artisan vendor:publish --tag=egn-config
```

## Използване

### Валидация

```php
use Ivajlokostov\LaravelEgn\Facades\Egn;

$isValid = Egn::validate('6101057509');
```

Или като Laravel validation rule:

```php
$request->validate([
    'egn' => ['required', 'egn'],
]);
```

### Парсване

```php
$parsed = Egn::parse('6101057509');
// ['year' => 1961, 'month' => 1, 'day' => 5, 'gender' => 0]
// gender: 0=female, 1=male
```

### Детайлен анализ (`array|object|collection`)

```php
$details = Egn::details('8702260780'); // array (default)
$detailsObject = Egn::details('8702260780', 'object');
$detailsCollection = Egn::details('8702260780', 'collection');
$detailsEn = Egn::details('8702260780', 'array', 'en'); // locale override
```

По подразбиране е `bg`. Ако не подадеш locale, пакетът ще използва `app()->getLocale()` само за `bg|en`, иначе пада обратно на `bg`.

Примерен резултат (`array`):

```php
[
    'egn' => '8702260780',
    'valid' => true,
    'locale' => 'bg',
    'gender' => 'жена',
    'gender_code' => 'female',
    'birth_date' => [
        'iso' => '1987-02-26',
        'weekday' => 'четвъртък',
        'formatted' => '26 февруари 1987 г. (четвъртък) (1987-02-26)',
    ],
    'age' => 38,
    'region' => [
        'code' => 78,
        'name' => 'Бургас',
    ],
    'birth_order' => 18,
    'zodiac' => [
        'name' => 'Риби',
        'label' => 'Риби (19 февруари - 20 март)',
    ],
]
```

### Генериране на 1 ЕГН

Всички опции са незадължителни.

```php
$egn = Egn::generateOne([
    'gender' => 'female', // male|female|m|f
    'year' => 1996,       // optional
    'month' => 8,         // optional
    'day' => 14,          // optional
    'region' => 22,       // optional, 0..99
]);
```

### Генериране на N ЕГН

```php
$egns = Egn::generate(50, [
    'month' => 2,
    'day' => 29,
]);
```

### Пълен `date` вместо `year/month/day`

```php
$egn = Egn::generateOne([
    'date' => '2004-06-12',
    'gender' => 'male',
]);
```

## Регионални кодове

| Код (value) | Регион |
|---|---|
| `0` | Случаен |
| `43` | Благоевград |
| `93` | Бургас |
| `139` | Варна |
| `169` | Велико Търново |
| `183` | Видин |
| `217` | Враца |
| `233` | Габрово |
| `281` | Кърджали |
| `301` | Кюстендил |
| `319` | Ловеч |
| `341` | Монтана |
| `377` | Пазарджик |
| `395` | Перник |
| `435` | Плевен |
| `501` | Пловдив |
| `527` | Разград |
| `555` | Русе |
| `575` | Силистра |
| `601` | Сливен |
| `623` | Смолян |
| `721` | София - град |
| `751` | София - окръг |
| `789` | Стара Загора |
| `821` | Добрич (Толбухин) |
| `843` | Търговище |
| `871` | Хасково |
| `903` | Шумен |
| `925` | Ямбол |
| `999` | Друг/Неизвестен |

## Конфигурация

`config/egn.php`

- `start_year` (default `1800`)
- `end_year` (default `2099`)

Използват се при случайна генерация, когато не е подадена година.

## API

- `validate(string $egn): bool`
- `parse(string $egn): ?array`
- `details(string $egn, string $format = 'array', ?string $locale = null): array|object|collection|null`
- `generateOne(array $options = []): string`
- `generate(int $count = 1, array $options = []): array`

## Тестове

```bash
composer test
```

## Website

- https://github.com/ivajlokostov/laravel-egn

## Лиценз

MIT
