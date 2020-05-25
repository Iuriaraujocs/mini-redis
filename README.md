This project implements a Redis subset (a in-memory NoSQL database) using PHP language working with socket on port 7080. 

The service listen to that port for requests, and can be used for multiple simultaneous connections. 
With the connection established, it offers the following commands:  
	"SET, GET, DEL, DBSIZE, INCR, ZADD, ZCARD, ZRANK and ZRANGE".

The Redis mini server can be started from the command line using the command "php <path> miniRedis.php", 
thus the same listens on port 7080 for requests. On linux Ubuntu 18.80 LTS, which was tested, the mini server can be accessed 
for testing from the command "telnet 127.0.0.1 7080" or any other type of connection on port 7080   


In this project, there is also the version of connection via http, which the client can connect with miniRedis via browser for example:

	"http: // localhost: 80 /? cmd = set% 20mykey% 20value" 

or by command line :
	curl "http: // localhost: 80 /? cmd = set% 20mykey% 20value"  

It can be easily adapted and installed in any hosting service in order to serve any application over the internet.