const express = require('express'),
    fs = require('fs'),
    https = require('https'),
    app = express(),
    httpsPort = process.argv.length > 2 ? Number(process.argv[2]) : 3000,
    oneYear = 1 * 365 * 24 * 60 * 60 * 1000;

const options = {
    key: fs.readFileSync(__dirname + '/ssl/key.key').toString(),
    cert: fs.readFileSync(__dirname + '/ssl/cert.pem').toString(),
};

app.use(express.static(__dirname + '/public', {maxAge: oneYear}));

https.createServer(options, app).listen(httpsPort, () => {
    console.log(`App listening on port ${httpsPort}`)
});
