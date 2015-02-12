# webchat
First of all: Don't hate me for this. :D I wrote it in about 4 hours and therefore it's ugly.

You can view the demo of this project at http://school.overflowerror.com/2013/info/chat (The server is not working at the moment; some sql-error; I already told the admin.).

My personal goal for this project was not to use AJAX-polling for looking for new messages. In order to reduce bandwidth.

So I made a server-side loop (ajax.php:69) which keeps polling the database for changes. If there are new messages, this script returns all new messages. After 55 loops the server-script returns some sort of "I'm sorry Dave, no new messages for you."-message. In either case the client-script starts the next request immediately.

So basically I converted network-load to server-processor-load. :smile:
