# Digikala supernova local environment. 
Author: Lukasz Lato - latolukasz@gmail.com

## Follow these steps to install this environment on your computer:

### 1. Install Docker and docker compose

Install it using [this link](https://download.docker.com/mac/edge/Docker.dmg).

Run it. Check if both tools works. First type in your terminal:

```bash
docker -v
```

You should see something like:

```bash
Docker version 17.06.0-ce, build 02c1d87
```

Now type:

```bash
docker-compose -v
```
You should see something like:

```bash
docker-compose version 1.14.0, build c7bdf9e
```

Configure docker to use half of the available CPU Cores and half of the available RAM

### 2. Install PhpStorm

Install it using [this link](https://www.jetbrains.com/phpstorm/download/#section=mac).

### 3. Clone supernova development environment

Open PhpStorm. On first window click on link 
"Checkout from Version Control" and choose "Github".
You need to provide your login credentials. Choose password 
from select and provide your github login and password.

Then from the list choose "https://github.com/digikala-supernova/supernova-env-dev.git".

If you can't see it please contact with someone that can grant you required
privileges to this repository.

### 4. Build environment
 
Open terminal in PhpStorm. You can find icon "Terminal" on bottom bar.
Remember this tool. You gonna use it many times.

Run:

```bash
./build.sh
```

You will be promted to provide your github personal token.
Open [this link](https://github.com/settings/tokens/new).
 In "Token description" field type "Supernova dev", check
 "repo" checkbox and click on "Generate token" button. 
 Copy generated token and paste in terminal and click enter.


After max 20 minutes your environment should be installed.

To make sure all services installed and run correctly, run below command in Phpstorm terminal:

```bash
docker-compose ps

state of all services should be UP.
```

Now you should be able to open all products. Open browser and
type one of:

* http://localhost:81 - Digikala Web/Mobile version/API
* http://localhost:82 - Digikala Back Office
* http://localhost:83 - Digikala Fulfillment
* http://localhost:84 - Digikala Delivery
* http://localhost:85 - Digikala Marketplace
* http://localhost:91 - Digistyle Web/Mobile version/API
* http://localhost:92 - Digistyle Back Office

If during this step faced these errors, flow the below instruction:
* ERROR: Couldn't connect to Docker daemon at http+docker://localunixsocket - is it running?
```bash
$ sudo service docker stop
$ sudo mv /var/lib/docker /var/lib/docker.bak
$ sudo service docker start
$ sudo groupadd docker
$ sudo usermod -aG docker $USER
After re-login problem solved.
```

* ERROR parsing HTTP 403 (Forbidden)
```bash
Connect via Kerio-VPN to solve the problem.
```

* ERROR: could not find an available, non-overlapping IPv4 address pool among the defaults to assign to the network
```bash
Disconect vpn and rerun build.sh
```

* if supernovaenvdev_elasticsearch_1 terminated with "Exit 78" 
```bash
sudo sysctl -w vm.max_map_count=262144
To set this value permanently, update the vm.max_map_count setting in /etc/sysctl.conf. 
```

### 5. Configure connection to database

Install Sequel Pro using [this link](https://sequelpro.com/).

Open it. Fill these fields:

* **Name** - Supernova local env dev
* **MySQL Host** - 127.0.0.1
* **Username** - root
* **Password** - root
* **Port** - 3307

Click on **Add to favorites** and connect to database.

### 6. Configure Google Chrome browser

Install Chrome browser. Open it and install two plugins:


* [Modify Headers plugin](https://chrome.google.com/webstore/detail/modify-headers-for-google/innpjfdalfhpcoinfnehdnbkglpmogdi)
* [Clockwork plugin](https://chrome.google.com/webstore/detail/clockwork/dmggabnehkmmfmdffgajcflpdjlnoemp)

Once it's installed please open Modify headers plugin (icon in top right corner).
Click on "Import" icon in top left corner and choose file "modifyheaders.json" from
your project root directory. Then please enable only "supernova-profiler" 
and "supernova-flush-opcache" headers.

Add new header and enable it:

* **action** - Add
* **name** - supernova-vendor-dir
* **description** - path to local vendor dir
* **value** - here paste full path to your vendor subdirectory on your local computer.
Simply right click on this directory in PHPStorm, choose "Copy Path" and paste it 
as value of header.

### 7. Configure PHPStorm

Open PhpStorm Preferences dialog (**Top Bar -> Preferences**).

#### 7.1. Multi repositories

Choose **Version Control**. In **Directory** field click on icon **+**. In new dialog in
field **Directory** click on **...** icon and choose directory 
**project_directory/vendor/digikala/supernova**. 
Click **ok**. You should see **Git** value in **CVS** fields.
Click ok. Now repeat the same process for all remaining subdirectories 
in **project_directory/vendor/digikala/** directory. Click **Apply** when all subdirectories
are visible in **Directory** field.

#### 7.2. PHP interpreter

Choose **Languages & Frameworks -> Php**.

In **PHP language level** choose **7.2**. 

In **CLI Interpreter** click on **...** icon. Then click on **+** icon 
and choose **From docker, vagrant, VM, remote** 
and later ***SSH credentials* option. Then set these fields:

* **Name** - PHP Remote
* **Remote** - SSH Credentials
* **Host** - 127.0.0.1
* **Port** - 23
* **User name** - root
* **Password** - root
* **PHP executable** - click on **...** and choose **/usr/bin/php7.2** file

if you got an error `no matching key exchange method found. Their offer: diffie-hellman-group1-sha1
` in linux or you need run ssh command you have to put below snippet in your `etc/ssh/ssh_config` file
```
Host 127.0.0.1
    KexAlgorithms +diffie-hellman-group1-sha1

```

Click on **save password** checkbox and later on **Apply** button.  
In field **Path mappings** click on **....** icon.
In **Local Path** choose root directory of your project. 
In **Remote Path** choose **/var/www**. When you save it you should see
**<Project root>->/var/www** in Path mappings field.

#### 7.3. PHPUnit

Choose **Languages & Frameworks -> Php -> Test frameworks**.

Click on **+** icon and choose **PHPUnit by Remote interpreter**.
Choose RemotePHP 7.2 from select and click ok. Choose **Use Composer autoloader**.
In field **Path to script** choose **/var/www/vendor/autoload.php**.


Click on **Apply**.

Right click in PhpStorm on all ***-tests.xml** files in every
**/vendor/digikala/** subdirectory and choose **Run xxx.xml** option.

#### 7.4. Excluded directories

Choose **Directories**.

Mark these directories as **Excluded**:

* /vendor/digikala/supernova-env-.../cache
* /vendor/digikala/supernova-env-.../web/static
* /storage

If you see other directores marked as **Excluded** unmark all of them (mark them as normal directories).

#### 7.5. Code sniffer

Choose **Languages & Framewoks -> PHP -> Code sniffer**.

Choose your Remote Interpreter. Remeber to uncheck checkbox "Visible only for this project".

Put "/var/www/vendor/bin/phpcs" in PHP Code Sniffer path.

Choose **Editor -> Inspections -> PHP**.

Check "PHP Code Sniffer validation". 
In "Coding standard" field choose "Custom" and in "Path to ruleset" type "/var/www/vendor/digikala/supernova/cs_ruleset.xml".

#### 7.6. Mess detector

Choose **Languages & Framewoks -> PHP -> Mess detector**.

Choose your Remote Interpreter. Remeber to uncheck checkbox "Visible only for this project".

Put "/var/www/vendor/bin/phpmd" in PHP Mess Detector path.

Choose **Editor -> Inspections -> PHP**.

Check "PHP Mess Detector validation". 

In "Custom rulesets" field add "/var/www/vendor/digikala/supernova/md_ruleset.xml".

Uncheck all checkboxes in "Options" section.

### Congrats.

You just configured your local Supernova development environment. Read these sections 
to learn how to use it:

In pogress...




