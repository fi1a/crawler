const socks = require('socksv5'),
    port = process.argv.length > 2 ? Number(process.argv[2]) : 50101,
    authUsername = process.argv.length > 2 ? process.argv[3] : null,
    authPassword = process.argv.length > 2 ? process.argv[4] : null;


let srv = socks.createServer(function(info, accept, deny) {
    accept();
});
srv.listen(port, '127.0.0.1', function() {
    console.log(`SOCKS server listening on port ${port}`);
});

srv.useAuth(socks.auth.UserPassword(function(user, password, cb) {
    cb(user === authUsername && password === authPassword);
}));