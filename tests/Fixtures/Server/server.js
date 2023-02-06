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

app.get('/502/', (req, res, next) => {
    res.status(502).send('Bad Gateway');
    next();
});

app.get('/api-key-auth/', (req, res, next) => {
    let token = req.header('token');
    if (req.query.token) {
        token = req.query.token;
    }

    if (token === '123') {
        res.status(200).send('Access granted');
        next();

        return;
    }

    res.status(401).send('Access denied');
    next();
});

app.use(express.static(__dirname + '/public', {maxAge: oneYear}));

https.createServer(options, app).listen(httpsPort, () => {
    console.log(`App listening on port ${httpsPort}`)
});
