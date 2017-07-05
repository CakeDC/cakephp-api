## CrudHateoas extension

HATEOAS, an abbreviation for *Hypermedia As The Engine Of Application State*,is a constraint of the REST 
application architecture that distinguishes it from most other network application architectures. 
The principle is that a client interacts with a network application entirely through hypermedia 
provided dynamically by application servers. 

A REST client needs no prior knowledge about how to interact with any particular application
or server beyond a generic understanding of hypermedia. 

The HATEOAS constraint decouples client and server in a way that allows the server functionality to evolve independently.

### How hyperlinks are generated

Any links represend as object with next keys: 
* `name` - link name
* `method` - HTTP method for the link
* `rel` - local api link.
* `href` - full url to the API.

Action generates links to related objects and links to itself with name as "self".

### Index action



#### Request: ``` http://example.com/api/posts?limit=3&include_direct=1 ````
```

```
{
    "status": "success",
    "data": [
        ...
    ],
    "pagination": {
        ...
    },
    "links": [
        {
            "name": "self",
            "href": "http:\/\/example.com\/api\/posts?limit=3&include_direct=1",
            "rel": "\/api\/posts?limit=3&include_direct=1",
            "method": "GET"
        },
        {
            "name": "posts:add",
            "href": "http:\/\/example.com\/api\/posts?limit=3&include_direct=1",
            "rel": "\/api\/posts?limit=3&include_direct=1",
            "method": "POST"
        }
    ]
}
```

### View action



#### Request: ``` http://example.com/api/posts/1?include_direct=1 ````
```

```
{
    "status": "success",
    "data": {
        "id": 1,
        "blog_id": 1,
        "name": "test",
        "body": "",
        "created": null,
        "modified": null,
        "blog": {
            "id": 1,
            "name": "alexis",
            "description": "alex blog",
            "created": null
        }
    },
    "links": [
        {
            "name": "self",
            "href": "http:\/\/example.com\/api\/posts\/1?include_direct=1",
            "rel": "\/api\/posts\/1?include_direct=1",
            "method": "GET"
        },
        {
            "name": "posts:edit",
            "href": "http:\/\/example.com\/api\/posts\/1?include_direct=1",
            "rel": "\/api\/posts\/1?include_direct=1",
            "method": "PUT"
        },
        {
            "name": "posts:delete",
            "href": "http:\/\/example.com\/api\/posts\/1?include_direct=1",
            "rel": "\/api\/posts\/1?include_direct=1",
            "method": "DELETE"
        },
        {
            "name": "posts:index",
            "href": "http:\/\/example.com\/api\/posts",
            "rel": "\/api\/posts",
            "method": "GET"
        }
    ]
}
```