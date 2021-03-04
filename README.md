# Bruno VIETNAM

[![Latest Version](https://img.shields.io/github/release/esbenp/bruno.svg?style=flat-square)](https://github.com/esbenp/bruno/releases)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![Build Status](https://img.shields.io/travis/esbenp/bruno/master.svg?style=flat-square)](https://travis-ci.org/esbenp/bruno)
[![Coverage Status](https://img.shields.io/coveralls/esbenp/bruno.svg?style=flat-square)](https://coveralls.io/github/esbenp/bruno)
[![Total Downloads](https://img.shields.io/packagist/dt/optimus/bruno.svg?style=flat-square)](https://packagist.org/packages/optimus/bruno)

## Giới thiệu

Một lớp bộ điều khiển cơ sở Laravel và một đặc điểm sẽ cho phép thêm tính năng lọc, sắp xếp, tải nhanh và phân trang cho
URL tài nguyên.

**Dành riêng cho Bruno**

Gói này được đặt theo tên anh hùng Giordano Bruno. Một người nhìn xa trông rộng thực sự, người dám ước mơ xa hơn những gì người ta nghĩ có thể.
Vì những ý tưởng của mình và việc từ chối từ bỏ chúng, ông đã bị thiêu sống vào năm 1600.
[Tôi thực sự giới thiệu phim hoạt hình ngắn này về cuộc đời của anh ấy do Neil deGrasse Tyson thuật lại](https://vimeo.com/89241669).

## Chức năng

* Phân tích cú pháp các thông số GET để tải động các tài nguyên liên quan, sắp xếp và phân trang
* Lọc tài nguyên nâng cao bằng cách sử dụng nhóm bộ lọc
* Sử dụng [Ninhtqse\Architect](https://github.com/ninhtqse/architect) để tải bên ngoài, tải id hoặc tải nhúng các tài nguyên liên quan

## Hướng dẫn

Để bắt đầu với Bruno, tôi thực sự giới thiệu bài viết về
[Kiểm soát tài nguyên trong API Laravel](http://esbenp.github.io/2016/04/15/modern-rest-api-laravel-part-2/)

## Cài đặt

```bash
composer require ninhtqse/bruno
```

## Sử dụng

Các ví dụ sẽ là một điểm cuối tài nguyên giả định `/books` sẽ trả về một collection `Book`,
thuộc về mỗi `Author`.

```
Book n ----- 1 Author
```

### Các tham số truy vấn có sẵn

Từ khóa | Kiểu | Mô tả
--- | ---- | -----------
Includes | array | Mảng tài nguyên liên kết để tải, e.g. ['author', 'publisher', 'publisher.books']
Sort | array | Thuộc tính sắp xếp theo, e.g. 'title'
Limit | integer | Giới hạn tài nguyên để trả lại
Page | integer | Để sử dụng có giới hạn
Filter_groups | array | Mảng các nhóm bộ lọc. Xem bên dưới để biết cú pháp.
Fields | array | Nhận các trường theo các tham số được truyền vào.
Skip | integer | Vị trí bắt đầu nằm trong cơ sở dữ liệu
Take | integer | Số lượng bản ghi muốn lấy

### Thực hiện

```php
<?php

namespace App\Http\Controllers;

use Ninhtqse\Api\Controller\EloquentBuilderTrait;
use Ninhtqse\Api\Controller\LaravelController;
use App\Models\Book;

class BookController extends LaravelController
{
    use EloquentBuilderTrait;

    public function getBooks()
    {
        // Parse the resource options given by GET parameters
        $resourceOptions = $this->parseResourceOptions();

        // Start a new query for books using Eloquent query builder
        // (This would normally live somewhere else, e.g. in a Repository)
        $query = Book::query();
        $this->applyResourceOptions($query, $resourceOptions);
        $books = $query->get();

        // Parse the data using Optimus\Architect
        $parsedData = $this->parseData($books, $resourceOptions, 'books');

        // Create JSON response of parsed data
        return $this->response($parsedData);
    }
}
```

## Tài liệu cú pháp

### Eager loading

**Simple eager load**

`/books?includes[]=author`

Sử dụng function relationship author trong model `Book` để load ra thêm mảng `Author`.

**IDs mode**

`/books?includes[]=author:ids`

Lấy ra tất cả id của `Author`.

**Sideload mode**

`/books?includes[]=author:sideload`

Tách mảng `Author` ra thêm 1 mảng riêng nữa. trong `Book` vẫn sẽ có ids của `Author`

[Xem về eager loading trong Ninhtqse\Architect's README](https://github.com/ninhtqse/architect)

### Phân trang

Hai tham số có sẵn: `limit` và `page`. `limit` sẽ xác định số lượng
bản ghi trên mỗi trang và `page` sẽ xác định trang hiện tại.

`/books?limit=10&page=3`

Sẽ trả lại bản ghi của book từ 30-40.

### Sắp xếp

Nên được định nghĩa là một mảng các quy tắc sắp xếp. Chúng sẽ được áp dụng trong
thứ tự mà chúng được xác định.

**Quy tắc sắp xếp**

Tính chất | Kiểu dữ liệu | Mô tả
-------- | ---------- | -----------
key | string | Thuộc tính của model sẽ sắp xếp theo
thuộc tính | ASC hoặc DESC | Sắp xếp thuộc tính nào

**Ví dụ**

```json
[
    {
        "key": "title",
        "direction": "ASC"
    }, {
        "key": "year",
        "direction": "DESC"
    }
]
```

Sẽ dẫn đến việc các sách được sắp xếp theo tên sách theo thứ tự tăng dần và sau đó là năm
thứ tự giảm dần.

### Lọc

Nên được định nghĩa là một mảng các nhóm bộ lọc.

**Filter groups**

Thuộc tính | Kiểu dữ liệu | Mô tả
-------- | ---------- | -----------
or | boolean | truyền vào toán tử OR hoặc AND, or = true sẽ là OR, or = false sẽ là AND
filters | array | Mảng bộ lọc (xem cú pháp bên dưới)

**Filters**

Thuộc tính | Kiểu dữ liệu | Mô tả
-------- | ---------- | -----------
key | string | Thuộc tính của model để lọc (cũng có thể là bộ lọc tùy chỉnh)
value | mixed | Giá trị cần tìm kiếm
operator | string | Toán tử bộ lọc để sử dụng (xem các loại khác nhau bên dưới)
not | boolean | Phủ nhận bộ lọc

**Operators**

Kiểu | Mô tả | Ví dụ
---- | ----------- | -------
ct | Chuỗi chứa | `ior` matches `Giordano Bruno` and `Giovanni`
sw | Bắt đầu với | `Gior` matches `Giordano Bruno` but not `Giovanni`
ew | Kết thúc với | `uno` matches `Giordano Bruno` but not `Giovanni`
eq | Bằng | `Giordano Bruno` matches `Giordano Bruno` but not `Bruno`
gt | Lớn hơn | `1548` matches `1600` but not `1400`
gte| Lớn hơn hoặc bằng | `1548` matches `1548` and above (ony for Laravel 5.4 and above)
lte | Nhỏ hơn hoặc bằng | `1600` matches `1600` and below (ony for Laravel 5.4 and above)
lt | Ít hơn | `1600` matches `1548` but not `1700`
in | Có tồn tại trong mảng | `['Giordano', 'Bruno']` matches `Giordano` and `Bruno` but not `Giovanni`
bt | Giữa | `[1, 10]` matches `5` and `7` but not `11`

**Giá trị đặc biệt**

Giá trị | Mô tả
----- | -----------
null (string) | Thuộc tính sẽ được kiểm tra giá trị NULL
(empty string) | Thuộc tính sẽ được kiểm tra giá trị NULL

#### Bộ lọc tùy chỉnh (Xem bên dưới tài liệu tiếng anh)
### Optional Shorthand Filtering Syntax (Xem bên dưới tài liệu tiếng anh)
-----------------------------------------------------------------------------------------------------------------------------------------
# Bruno ENGLISH

[![Latest Version](https://img.shields.io/github/release/esbenp/bruno.svg?style=flat-square)](https://github.com/esbenp/bruno/releases)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![Build Status](https://img.shields.io/travis/esbenp/bruno/master.svg?style=flat-square)](https://travis-ci.org/esbenp/bruno)
[![Coverage Status](https://img.shields.io/coveralls/esbenp/bruno.svg?style=flat-square)](https://coveralls.io/github/esbenp/bruno)
[![Total Downloads](https://img.shields.io/packagist/dt/optimus/bruno.svg?style=flat-square)](https://packagist.org/packages/optimus/bruno)

## Introduction

A Laravel base controller class and a trait that will enable to add filtering, sorting, eager loading and pagination to your
resource URLs.

**Dedicated to Giordano Bruno**

This package is named after my hero Giordano Bruno. A true visionary who dared to dream beyond what was thought possible.
For his ideas and his refusal to renounce them he was burned to the stake in 1600.
[I highly recommend this short cartoon on his life narrated by Neil deGrasse Tyson](https://vimeo.com/89241669).

## Functionality

* Parse GET parameters for dynamic eager loading of related resources, sorting and pagination
* Advanced filtering of resources using filter groups
* Use [Optimus\Architect](https://github.com/esbenp/architect) for sideloading, id loading or embedded loading of related resources
* ... [Ideas for new functionality is welcome here](https://github.com/esbenp/bruno/issues/new)

## Tutorial

To get started with Bruno I highly recommend my article on
[resource controls in Laravel APIs](http://esbenp.github.io/2016/04/15/modern-rest-api-laravel-part-2/)

## Installation

```bash
composer require ninhtqse/bruno
```

## Usage

The examples will be of a hypothetical resource endpoint `/books` which will return a collection of `Book`,
each belonging to a `Author`.

```
Book n ----- 1 Author
```

### Available query parameters

Key | Type | Description
--- | ---- | -----------
Includes | array | Array of related resources to load, e.g. ['author', 'publisher', 'publisher.books']
Sort | array | Property to sort by, e.g. 'title'
Limit | integer | Limit of resources to return
Page | integer | For use with limit
Filter_groups | array | Array of filter groups. See below for syntax.
Fields | array | Get the fields according to the parameters passed.
Skip | integer | The starting position is in the database
Take | integer | Number of records taken

### Implementation

```php
<?php

namespace App\Http\Controllers;

use Ninhtqse\Api\Controller\EloquentBuilderTrait;
use Ninhtqse\Api\Controller\LaravelController;
use App\Models\Book;

class BookController extends LaravelController
{
    use EloquentBuilderTrait;

    public function getBooks()
    {
        // Parse the resource options given by GET parameters
        $resourceOptions = $this->parseResourceOptions();

        // Start a new query for books using Eloquent query builder
        // (This would normally live somewhere else, e.g. in a Repository)
        $query = Book::query();
        $this->applyResourceOptions($query, $resourceOptions);
        $books = $query->get();

        // Parse the data using Optimus\Architect
        $parsedData = $this->parseData($books, $resourceOptions, 'books');

        // Create JSON response of parsed data
        return $this->response($parsedData);
    }
}
```

## Syntax documentation

### Eager loading

**Simple eager load**

`/books?includes[]=author`

Will return a collection of 5 `Book`s eager loaded with `Author`.

**IDs mode**

`/books?includes[]=author:ids`

Will return a collection of `Book`s eager loaded with the ID of their `Author`

**Sideload mode**

`/books?includes[]=author:sideload`

Will return a collection of `Book`s and a eager loaded collection of their
`Author`s in the root scope.

[See mere about eager loading types in Optimus\Architect's README](https://github.com/esbenp/architect)

### Pagination

Two parameters are available: `limit` and `page`. `limit` will determine the number of
records per page and `page` will determine the current page.

`/books?limit=10&page=3`

Will return books number 30-40.

### Sorting

Should be defined as an array of sorting rules. They will be applied in the
order of which they are defined.

**Sorting rules**

Property | Value type | Description
-------- | ---------- | -----------
key | string | The property of the model to sort by
direction | ASC or DESC | Which direction to sort the property by

**Example**

```json
[
    {
        "key": "title",
        "direction": "ASC"
    }, {
        "key": "year",
        "direction": "DESC"
    }
]
```

Will result in the books being sorted by title in ascending order and then year
in descending order.

### Filtering

Should be defined as an array of filter groups.

**Filter groups**

Property | Value type | Description
-------- | ---------- | -----------
or | boolean | Should the filters in this group be grouped by logical OR or AND operator
filters | array | Array of filters (see syntax below)

**Filters**

Property | Value type | Description
-------- | ---------- | -----------
key | string | The property of the model to filter by (can also be custom filter)
value | mixed | The value to search for
operator | string | The filter operator to use (see different types below)
not | boolean | Negate the filter

**Operators**

Type | Description | Example
---- | ----------- | -------
ct | String contains | `ior` matches `Giordano Bruno` and `Giovanni`
sw | Starts with | `Gior` matches `Giordano Bruno` but not `Giovanni`
ew | Ends with | `uno` matches `Giordano Bruno` but not `Giovanni`
eq | Equals | `Giordano Bruno` matches `Giordano Bruno` but not `Bruno`
gt | Greater than | `1548` matches `1600` but not `1400`
gte| Greater than or equalTo | `1548` matches `1548` and above (ony for Laravel 5.4 and above)
lte | Lesser than or equalTo | `1600` matches `1600` and below (ony for Laravel 5.4 and above)
lt | Lesser than | `1600` matches `1548` but not `1700`
in | In array | `['Giordano', 'Bruno']` matches `Giordano` and `Bruno` but not `Giovanni`
bt | Between | `[1, 10]` matches `5` and `7` but not `11`

**Special values**

Value | Description
----- | -----------
null (string) | The property will be checked for NULL value
(empty string) | The property will be checked for NULL value

#### Custom filters

Remember our relationship `Books n ----- 1 Author`. Imagine your want to
filter books by `Author` name.

```json
[
    {
        "filters": [
            {
                "key": "author",
                "value": "Optimus",
                "operator": "sw"
            }
        ]
    }
]
```

Now that is all good, however there is no `author` property on our
model since it is a relationship. This would cause an error since
Eloquent would try to use a where clause on the non-existant `author`
property. We can fix this by implementing a custom filter. Where
ever you are using the `EloquentBuilderTrait` implement a function named
`filterAuthor`

```php
public function filterAuthor(Builder $query, $method, $clauseOperator, $value)
{
    // if clauseOperator is idential to false,
    //     we are using a specific SQL method in its place (e.g. `in`, `between`)
    if ($clauseOperator === false) {
        call_user_func([$query, $method], 'authors.name', $value);
    } else {
        call_user_func([$query, $method], 'authors.name', $clauseOperator, $value);
    }
}
```

*Note:* It is important to note that a custom filter will look for a relationship with
the same name of the property. E.g. if trying to use a custom filter for a property
named `author` then Bruno will try to eagerload the `author` relationship from the
`Book` model.

**Custom filter function**

Argument | Description
-------- | -----------
$query | The Eloquent query builder
$method | The where method to use (`where`, `orWhere`, `whereIn`, `orWhereIn` etc.)
$clauseOperator | Can operator to use for non-in wheres (`!=`, `=`, `>` etc.)
$value | The filter value
$in | Boolean indicating whether or not this is an in-array filter

#### Examples

```json
[
    {
        "or": true,
        "filters": [
            {
                "key": "author",
                "value": "Optimus",
                "operator": "sw"
            },
            {
                "key": "author",
                "value": "Prime",
                "operator": "ew"
            }
        ]
    }
]
```

Books with authors whoose name start with `Optimus` or ends with `Prime`.

```json
[
    {
        "filters": [
            {
                "key": "author",
                "value": "Brian",
                "operator": "sw"
            }
        ]
    },
    {
        "filters": [
            {
                "key": "year",
                "value": 1990,
                "operator": "gt"
            },
            {
                "key": "year",
                "value": 2000,
                "operator": "lt"
            }
        ]
    }
]
```

Books with authors whoose name start with Brian and which were published between years 1990 and 2000.

### Optional Shorthand Filtering Syntax (Shorthand)
Filters may be optionally expressed in Shorthand, which takes the a given filter
array[key, operator, value, not(optional)] and builds a verbose filter array upon evaluation.

For example, this group of filters (Verbose)
```json
[
    {
        "or": false,
        "filters": [
            {
                "key": "author",
                "value": "Optimus",
                "operator": "sw"
            },
            {
                "key": "author",
                "value": "Prime",
                "operator": "ew"
            }
            {
                "key": "deleted_at",
                "value": null,
                "operator": "eq",
                "not": true
            }
        ]
    }
]
```
May be expressed in this manner (Shorthand)
```json
[
    {
        "or": false,
        "filters": [
            ["author", "sw", "Optimus"],
            ["author", "ew", "Prime"],
            ["deleted_at", "eq", null, true]
        ]
    }
]
```


## Standards

This package is compliant with [PSR-1], [PSR-2] and [PSR-4]. If you notice compliance oversights,
please send a patch via pull request.

[PSR-1]: https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-1-basic-coding-standard.md
[PSR-2]: https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md
[PSR-4]: https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-4-autoloader.md

## Contributing

Please see [CONTRIBUTING](https://github.com/esbenp/bruno/blob/master/CONTRIBUTING.md) for details.

## License

The MIT License (MIT). Please see [License File](https://github.com/esbenp/bruno/blob/master/LICENSE) for more information.
