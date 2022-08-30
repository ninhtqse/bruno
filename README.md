# Bruno

[![Latest Version](https://img.shields.io/github/release/ninhtqse/bruno.svg?style=flat-square)](https://github.com/ninhtqse/bruno/releases)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![Build Status](https://img.shields.io/travis/ninhtqse/ninhtqse/master.svg?style=flat-square)](https://travis-ci.org/ninhtqse/bruno)
[![Coverage Status](https://img.shields.io/coveralls/ninhtqse/bruno.svg?style=flat-square)](https://coveralls.io/github/ninhtqse/bruno)
[![Total Downloads](https://img.shields.io/packagist/dt/ninhtqse/bruno.svg?style=flat-square)](https://packagist.org/packages/ninhtqse/bruno)

## Giới thiệu

Sử dụng với chức năng phân trang, sort, query tại client,...

**Dành riêng cho Bruno**

Gói này được đặt theo tên anh hùng Giordano Bruno. Một người nhìn xa trông rộng thực sự, người dám ước mơ xa hơn những gì người ta nghĩ có thể.
Vì những ý tưởng của mình và việc từ chối từ bỏ chúng, ông đã bị thiêu sống vào năm 1600.
[Tôi thực sự giới thiệu phim hoạt hình ngắn này về cuộc đời của anh ấy do Neil deGrasse Tyson thuật lại](https://vimeo.com/89241669).

## Chức năng

* Phân tích cú pháp các thông số GET để tải động các tài nguyên liên quan, sắp xếp và phân trang
* Lọc tài nguyên nâng cao bằng cách sử dụng nhóm bộ lọc
* Sử dụng [ninhtqse\architect](https://github.com/ninhtqse/architect) để tải bên ngoài, tải id hoặc tải nhúng các tài nguyên liên quan

## Cài đặt

```bash
composer require ninhtqse/bruno
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
Not_fields | boolean | Trừ các trường còn lại lấy hết
Filter_or | boolean | Dùng đi kèm với Filter_groups dùng để thay đổi điều kiện and/or

## Sử dụng

=======================**Includes**=======================

+ Dùng để lấy ra dữ liệu của các bảng liên kết
+ Ví dụ bảng users liên kết 1 nhiều với bảng blogs

VD:
```string
localhost/users?includes[]=blogs
```
+ Có thể includes nhiều bảng khác nhau, nếu là (1 - nhiều) sẽ có s đằng sau tên bảng :
VD: 
```string
localhost/users?includes[]=blogs&includes[]=options
```


=======================**Sort**=======================

+ Dùng để sắp xếp dữ liệu theo các trường
+ Có 2 giá trị cần truyền vào sort 
+ Có thể truyền nhiều trường để sort

**Tham số**

Tên trường | Giá trị | Mô tả
-------- | ---------- | -----------
key | string | Tên trường
direction | ASC or DESC | Kiểu sắp xếp

**Ví dụ**

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

VD:
```string
localhost/users?sort[0][key]=title&sort[0][direction]=asc&sort[1][key]=title&sort[1][direction]=asc
```


=======================**Limit**=======================

+ Dùng để lấy ra số bản ghi nhất định 

VD:
```string
localhost/users?limit=10
```

=======================**Page**=======================

+ Dùng để phân trang . Page bắt buộc phải đi với limit

VD: 
Tổng có 50 bản ghi nhưng mỗi trang chỉ lấy 10 bản ghi => có 5 trang. Page là số từ 1->5
```string
localhost/users?limit=10&page=2
```
=======================**Filter_groups**=======================

+ Dùng để query phía client
+ Filter_groups có 4 tham số

**Tham số**

Trường | Kiểu dữ liệu | Mô tả
-------- | ---------- | -----------
key | string | Trường trong bảng
value | mixed | Giá trị
operator | string | Toán tử
not | boolean | Phủ nhận bộ lọc

**Toán tử**

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
eqd | So sánh ngày tháng năm | 2022-01-21 - Dùng để filter các trường là datetime
eqy | So sánh năm | 2022
eqm | So sánh tháng | 01

**Giá trị đặc biệt**

Giá trị | Mô tả
----- | -----------
null (string) | Thuộc tính sẽ được kiểm tra giá trị NULL
(empty string) | Thuộc tính sẽ được kiểm tra giá trị NULL

```array
[
    [
        "filters" => [
            [
                "key" => "acreage"
                "operator" => "bt"
                "value" => "[101,200]"
                "not" => false
            ]
        ]
        "or" => false
    ]
]
```
VD: 
```string
localhost/users?filter_groups[0][filters][1][key]=hierarchy&filter_groups[0][filters][1][operator]=eq&filter_groups[0][filters][1][value]=13&filter_groups[1][filters][1][key]=floor_plan&filter_groups[1][filters][1][operator]=eq&filter_groups[1][filters][1][value]=1LDK
```
SQL:

```string
select * from `rooms` where (`rooms`.`hierarchy` = 13) and (`rooms`.`floor_plan` = 1LDK)
```

+ Trường filter_groups[0] là dấu ngoặc đầu tiên của câu lệnh SQL bên trên | trường filter_groups[1] là dấu ngoặc thứ 2 sau and
+ Mạc định giữa các ngoặc lớn sẽ là điều kiện sẽ là and
+ Các mảng nhỏ trong trường filters sẽ nằm trong ngoặc lớn 
VD:
```string
localhost/users?filter_groups[0][filters][1][key]=hierarchy&filter_groups[0][filters][1][operator]=eq&filter_groups[0][filters][1][value]=13&filter_groups[0][filters][2][key]=floor_plan&filter_groups[0][filters][2][operator]=eq&filter_groups[0][filters][2][value]=1LDK
```
SQL:
```string
select * from `rooms` where (`rooms`.`hierarchy` = 13 and `rooms`.`floor_plan` = 1LDK)
```
+ Trường not: Nếu bằng true sẽ (phủ định|ngược lại) của toán tử (operator)
+ Với các trường nhỏ bên trong filters. Sử dụng or để đổi lại toán tử => Mạc định là and

=======================**Filter_or**=======================

Sử dụng bắt buộc phải có filter_groups
+ Như đã nói bên trên "Mạc định giữa các ngoặc lớn sẽ là điều kiện sẽ là and"
+ Nếu muốn là or ta truyền : 

VD:
```string
localhost/users?filter_groups[0][filters][1][key]=hierarchy&filter_groups[0][filters][1][operator]=eq&filter_groups[0][filters][1][value]=13&filter_groups[1][filters][1][key]=floor_plan&filter_groups[1][filters][1][operator]=eq&filter_groups[1][filters][1][value]=1LDK&filter_or[0]=true
```

SQL:
```string
select * from `rooms` where (`rooms`.`hierarchy` = 13) or (`rooms`.`floor_plan` = 1LDK)
```


=======================**Fields**=======================

Sử dụng để lấy ra các trường cần thiết
+ Ví dụ có 100 trường nhưng chỉ lấy 1 trường

```string
localhost/users?fields[]=name&fields[]=test
```

=======================**Skip**=======================

Sử dụng để lấy ra vị trí trong sql | bắt buộc phải đi với take
+ Ví dụ lấy ra 10 bản ghi dùng skip để lấy từ bản ghi số 5 trở đi

```string
localhost/users?skip=5&take=10
```

=======================**Take**=======================

Sử dụng để lấy ra số bản ghi mong muốn
+ Ví dụ lấy ra 10

```string
localhost/users?take=10
```

=======================**Not_fields**=======================

Sử dụng để loại bỏ các bản ghi không cần thiết
+ VD bảng có 100 trường lấy 99 trường
+ Nếu dùng fields phải liệt kê quá nhiều

```string
localhost/users?not_fields[]=name
```
