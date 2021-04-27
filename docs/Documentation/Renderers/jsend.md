JSend
=====

How to use?
-----------

By default we are already using this configuration. Then you don't need to do anything more unless the basic configuration.


Response Types
--------------

**Success:** When an API call is successful, the JSend object is used as a simple envelope for the results, using the data key, as in the following example:

**GET**

When you use this route */api/list* and you have two controllers "Articles" and "Posts" for example you shall get this result:

```
{
    "status": "success",
    "data": [
        "articles",
        "posts"
    ],
    "links": []
}
```

**POST**

When you use this route */api/describe?token=YOUR_AUTH_TOKEN*. It will describe your service. Here, we are shown an example using a service of the plugin.

We need to send three fields in the post to do this request.

```
username: user
password: password
service: listing
```

This will give a response like this:
```
{
    "status": "success",
    "data": [],
    "links": []
}
```

