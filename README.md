# GCPCachingIssue
Hello,  I'm using AppEngine with PHP runtime and using Datastore thanks to the Google APIs Client Library for PHP (which is using the JSON Datastore API).

After making some analysis on why requests are taking so much time, I'm seeing that there is an inner request that is made upon the first Datastore call for credentials purpose: url.fetch https://www.googleapis.com/oauth2/v4/token which is taking on average 350ms!!!

I've [read here](https://developers.google.com/api-client-library/php/start/get_started#google-app-engine-support) that:
"This library works well with Google App Engine applications. The Memcache class is automatically used for caching, and the file IO is implemented with the use of the Streams API."

But unfortunately it doesn't handle it at all, and this project demonstrates the issue.

Indeed every single request handled by the AppEngine instance will trigger a token request...
If the App Engine handled request make multiple Datastore requests only the first one will trigger a token request (fortunately)
But between App Engine requests, there is no token persistence at all!
