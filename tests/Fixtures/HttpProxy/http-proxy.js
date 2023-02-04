const proxyChain = require('proxy-chain'),
    port = process.argv.length > 2 ? Number(process.argv[2]) : 50100,
    authUsername = process.argv.length > 2 ? process.argv[3] : null,
    authPassword = process.argv.length > 2 ? process.argv[4] : null;

const server = new proxyChain.Server({
    port,
    prepareRequestFunction: ({ request, username, password, hostname, port, isHttp, connectionId }) => {
        return {
            // If set to true, the client is sent HTTP 407 resposne with the Proxy-Authenticate header set,
            // requiring Basic authentication. Here you can verify user credentials.
            requestAuthentication: username !== authUsername || password !== authPassword,

            // If "requestAuthentication" is true, you can use the following property
            // to define a custom error message to return to the client instead of the default "Proxy credentials required"
            failMsg: 'Bad username or password, please try again.'
        };
    },
});

server.listen(() => {
    console.log(`Proxy server is listening on port ${port}`);
});
