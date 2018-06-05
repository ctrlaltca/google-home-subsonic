# google-home-subsonic
Google home integration with subsonic

First of all, this project is still a work in progress and is nowhere usable by a normal user.
It's basically a php script receiving actions from Google home and passing them to Subsonic's REST API.

In order to get this working you need:
 * From a working subsonic installation: its url, a username and a password;
 * A internet-facing webserver able to run php files, with the php "curl" extension installed;
 * A Google account already configured on your device;
 * Time and patience.

First, copy the contents of the "www" folder of the project on the webserver.
Edit the `config.php` file and fill your subsonic server's url and credentials.
Write down the full url to index.php, eg: https://www.myserver.com/subsonic/index.php: you'll need this later.

Now, time to configure the actions on Google:

1. Log into your Google account and go to the Actions Console: https://console.actions.google.com/
2. Add a project, name it "Subsonic" and confgiure the basic settings. Since google won't accept the single word "Subsonic" as an invocation name, i'm using "Sub sonic" as a workaround
3. Now move to the "Actions" section, add an action, choose "custom intent" and press "BUILD"
4. You should be taken to the Dialogflow console.
5. In the "Intents" section, upload the prebuilt intents from the "intents" folder of the project
6. In the "Fulfillment" section, enable the webhook and enter the full url to index.php.

Since my actions are currently written in Italian, you may want to translate them to the language you are using on your Google home.
You should be ready to test them in the simulator now.
Use the "Ok Google, talk with Sub sonic" to start the session and then ask it to lookup a song.
