# Database schema

For demo calls we will have this tables created.

```sql
CREATE TABLE `blogs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  PRIMARY KEY (`id`)
);

CREATE TABLE `posts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `blog_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `body` text NOT NULL,
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  PRIMARY KEY (`id`)
);

```




## Index


### Success request.

```bash
curl -i https://myapi.com/api/blogs?limit=1
```

```json
{
    "status": "success",
    "data": [
        {
            "id": 1,
            "name": "alex",
            "description": "",
            "created": null
        },
        {
            "id": 2,
            "name": "frenk",
            "description": "",
            "created": null
        },
        {
            "id": 3,
            "name": "john",
            "description": "",
            "created": "2016-03-17T23:31:13+00:00"
        },
        {
            "id": 4,
            "name": "mark",
            "description": "",
            "created": null
        },
    ],
    "pagination": {
        "page": 1,
        "limit": 4,
        "pages": 6,
        "count": 23
    }
}
```

## View

### Success request.

```bash
curl -i https://myapi.com/api/blogs/1
```

```json
{
    "status": "success",
    "data": {
        "id": 1,
        "name": "alexis",
        "description": "alex blog",
        "created": null
    }
}
```
	
### Failed request.

```bash
curl -i https://myapi.com/api/blogs/9999
```

```json
{
    "status": "error",
    "message": "Record not found in table \"blogs\"",
    "code": 404,
    "data": null
}
```

## Add

```bash
curl --data "name=Jorge&description=Jorge blog" https://myapi.com/api/blogs
```

```json
{
    "status": "success",
    "data": {
        "name": "Jorge",
        "description": "Jorge blog",
        "created": "2016-10-24T23:08:39+00:00",
        "id": 25
    }
}
```

## Edit

```bash
curl -X PUT --data "description=Jorge best blog" https://myapi.com/api/blogs/25
```

```json
{
    "status": "success",
    "data": {
        "id": 25,
        "name": "Jorge",
        "description": "Jorge best blog",
        "created": "2016-10-24T23:13:35+00:00"
    }
}
```

## Edit request with POST instead of PUT

```bash
curl --data "description=Jorge best blog" https://myapi.com/api/blogs/25
```

```json
{
    "status": "error",
    "message": "A route matching \"\/blogs\/26\" could not be found.",
    "code": 404,
}
```

### Data validation failed request.

```bash
curl --data "description=Jorge" https://myapi.com/api/blogs
```

```json
{
    "status": "error",
    "message": "Validation failed",
    "code": 422,
    "data": {
        "name": {
            "_required": "This field is required"
        }
    }
}
```

## Delete

### Success request.

```bash
curl -X DELETE https://myapi.com/api/blogs/25
```

```json
{
    "status": "success",
    "data": true
}
```

### Failed request.

```bash
curl -X DELETE https://myapi.com/api/blogs/9999
```

```json
{
    "status": "error",
    "message": "Record not found in table \"blogs\"",
    "code": 404,
    "data": null
}
```

## Nested resources.

```bash
curl -i https://myapi.com/api/blogs/1/posts
```

```json
{
    "status": "success",
    "data": [
        {
            "id": 1,
            "blog_id": 1,
            "name": "test",
            "body": "",
            "created": null,
            "modified": null
        },
        {
            "id": 2,
            "blog_id": 1,
            "name": "aaa",
            "body": "",
            "created": null,
            "modified": null
        }
    ],
    "pagination": {
        "page": 1,
        "limit": 20,
        "pages": 1,
        "count": 2
    }
}
```

## Auth

### Failed auth request

```bash
curl -i https://myapi.com/api/blogs/1/posts?token=WrongToken
```

```json
{

    "status": "error",
    "message": "Unauthenticated",
    "code": 403,
    "data": null
}
```
