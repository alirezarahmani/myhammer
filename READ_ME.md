Hello

MyHammer Test!
===

I create a docker file and set-up environment for you to run this project easily. The only thing you should do is just run `./build.sh` in root of project. It may take a short time to download docker images. After the progress finished try `localhost:81` and There you are. 

Run a Script or Command:
---

You need to run no extra command or scripts. when you run `./build.sh` the system will make everything (such as migrations, composer install) ready for you!

About Architecture:
---
This project I do not use any Framework, But I use lots of Symfony components(I am big fan of symfony Components). It's base on very simple layered architecture(not MVC).

What about Libraries:
---
I use some extra libraries, such as ORM and Asserts. What are them exactly? ORM is base on memcached, I mean first data will save to memcacheD and after that will save in mysql. My read queries I called them cache Indices will hit memcacheD and if not find it will go to mysql. We wrote this ORM when I was in poland, We use it in many enterprise we applications it's super fast and very reliable and maintainable. 