Hello

MyHammer Test!
===

I create a docker file and set-up environment for you to run this project easily.
The only thing you should do is just run `./build.sh` in root of project.
It may take a short time to download docker images.
After the progress finished. just try `localhost:81` and There you are. 

Run a Script or Command:
---

You need to run no extra command or scripts.
when you run `./build.sh` the system will make everything (such as migrations, composer install) ready for you!

My Assumptions:
---
- I assume the form in pdf file is for customers that can demand a job (or a action) to be done by some experts and some experts can see the demands related to their profession  and accept to do it.
- I assume values in the filed `when job should be done` in pdf file are fixed(such as should be done immediately or by next days)values and should not be a date. 
- I assume `city` and `zip code` are fixed values(the table in pdf file) and they will not change or change very rarely, then I create a value object for them.

About Architecture:
---
In this project I do not use any Framework, But I use lots of Symfony components(I am big fan of symfony Components).
It's base on very simple layered architecture(not MVC).

What about Libraries:
---
I use some extra libraries, such as ORM and Asserts.
What are them exactly? ORM is base on memcached, I mean first data will save to memcacheD and after that will save in mysql.
My read queries I called them cache Indices will hit memcacheD and if not find it will go to mysql.
We wrote this ORM when I was in poland, We use it in many enterprise we applications it's super fast and very reliable and maintainable.

What Are To Dos:
--
I put some `@todo` in code, because I do not have enough time to finish them. If you do care just give me more days to finish them.

Documents for Mobile Applications Developers:
--
In documents folder I've created a comprehensive documents for them.
 
Important Notes:
---
The `router` is not well done, it's only support simple routes. If you do care just give me a time to create Route Listener and make it complete. 