# Introduction
This is a remoteStorage server implementation written in PHP. It aims at 
implementing `draft-dejong-remotestorage-01.txt`.

# License
Licensed under the GNU Affero General Public License as published by the Free 
Software Foundation, either version 3 of the License, or (at your option) any 
later version.

    https://www.gnu.org/licenses/agpl.html

This rougly means that if you use this software in your service you need to 
make the source code available to the users of your service (if you modify
it). Refer to the license for the exact details.

# Installation
Below are installation instructions for various operating systems and for both
Apache and NGINX. Please note that Apache is not working well at the moment due
to an issue related to Cross Origin Resource Sharing, see issues below.

## Debian/Ubuntu

* xattr/mime handler
* sendfile
* import some files and set the mimetype from command line

### Apache
* apache config

### NGINX

## Fedora/CentOS/RHEL

* xattr/mime handler
* sendfile
* apache config
* import some files and set the mimetype from command line

### Apache
* apache config

### NGINX

# Configuration
* file paths
* oauth AS configuration
* mime type / sendfile stuff

# Issues
There is a problem with using the Apache web server. Apache will throw away
Cross Origin Resource Sharing (CORS) headers on a 304 response.

** Link to Apache bug
