# Church Community Builder API Consumer Application Example

This is a sample CCB API Consumer application written in Python + Flask. It is
intended to show how an application can connect to the CCB API via the OAuth 2
protocol.

## Requirements

You must have python 3.6+ installed to run this application.

You'll need to use a terminal emulator such as bash or powershell to work with
this application.

To use the Makefile you must have some version of make installed. If you do
not or cannot install it, checkout the Makefile to see the commands. You can
run them in your terminal manually.

## Setup

To install the dependencies, in your terminal run `make install`.

## Running the Application

To run the application, you'll need to set your CCB API OAuth client
credentials as environment variables, e.g.:

    export CCB_CLIENT_ID=<your client ID>
    export CCB_CLIENT_KEY=<your client key / secret>

With those variables set in the environment, you can then run the application
via

    make run

This will start the Flask server on localhost, port 8080. You can then access
the running application at [http://localhost:8080](http://localhost:8080).

## Resetting the Data Store

The application uses a simple JSON-based file storage. This is present just
as a simple storage means for this sample application. Once you have gone
through the authorization flow once, the application will use the refresh
token to keep the acess token fresh. If you want to go through the
authorization flow again, run `make reset` to clear the database.

## How the Application Works

The application will first allow you to connect up to the CCB API through the
three-legged OAuth flow. Once you finish connecting the application you will
be redirected back to the locally running application and will be presented
with a paged church directory.

## Notes About the Application

This application was designed solely as an example of how to connect to the
CCB API with Python. There is no error handling, logging, or other things you
would normally want in a production application.

Also note: this application stores access and refresh tokens in plain text,
you _should not_ do this in production. Access tokens grant full access to
resources, anyone with the access token will be able to impersonate you until
the access token expires.

If you fork this code to create an application *please do not hard code your
OAuth client ID and Secret!* If you do, anyone who has access to the code base
will have access to your client credentials.
