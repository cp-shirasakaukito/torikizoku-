var http = require('http');
var url = require('querystring');

var server = http.createServer();
server.on('request', function(request, response){
    //if(request.method == 'GET'){
    //    var get_data = url.parse(request.url, true);
    //    response.end(get_data['query']['query']);
    //}
    if(request.method == 'POST'){
        var data = '';
        request.on('data', function (chunk) {
            data += chunk
        });
        response.end(data);
    } else {
        response.end('no post');
    }
    response.writeHead(200, {'Content-Type': 'text/plain'});
    response.end("hello");
});
server.listen(8124,'127.0.0.1');
console.log('Server running at http://localhost:8124');