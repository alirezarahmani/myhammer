# Application Developer API


## Post [/demands]
+ Parameters
    + title: `I need a craftsman` Title of a job.
    + zip_code: `10115` (string) - valid zip code of germany.
    + city: `Berlin` (string) - city in germany.
    + execute_time: `immediately` (string) - the execution time of task. only (week, three_days, immediately).
    + category_id: `804040` (integer) - the valid category id.
    + description: `hello ` (string) - the description of job.

+ Success (application/json)

    ```js
    {
        Data: [ ],
        Status: true,
        Message: ""
    }
    ```

+ Error (application/json)

    ```js
    {
        Data: [ ],
        Status: false,
        Message: {
          zip_code: "Zip: german zip code only"
        }
    }
    ```
    
## PUT [/demands/{id}]
+ Parameters
    + title: `I need a craftsman` Title of a job.
    + zip_code: `10115` (string) - valid zip code of germany.
    + city: `Berlin` (string) - city in germany.
    + execute_time: `immediately` (string) - the execution time of task. only (week, three_days, immediately).
    + category_id: `804040` (integer) - the valid category id.
    + description: `hello ` (string) - the description of job.

+ Success (application/json)

    ```js
    {
        Data: [ ],
        Status: true,
        Message: ""
    }
    ```

+ Error (application/json)

    ```js
    {
        Data: [ ],
        Status: false,
        Message: {
          zip_code: "Zip: german zip code only"
        }
    }
    ``` 
    
## GET [/jobs]
+ Parameters (optional)
    + zip_code: `10115` (string) - valid zip code of germany.
    + city: `Berlin` (string) - city in germany.
    + category_id: `804040` (integer) - the valid category id.

+ Success (application/json)

    ```js
    {
    Data: [
        {
        title: "hello i need someone",
        category: 'Fensterreinigung',
        execute time: "immediately",
        address: "the Address is in Germany, Berlin city, with zip code : 10115"
        }, {
        title: "Need a craftsman",
        category: 'Fensterreinigung',
        execute time: "immediately",
        address: "the Address is in Germany, Berlin city, with zip code : 10115"
        }
    ],
    Status: true,
    Message: ""
    }
    ```

+ Error (application/json)

    ```js
    {
        Data: [ ],
        Status: false,
        Message: {
          zip_code: "Zip: german zip code only"
        }
    }
    ```     