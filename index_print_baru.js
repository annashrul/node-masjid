const escpos = require('escpos');
var sprintf = require('locutus/php/strings/sprintf');
var echo = require('locutus/php/strings/echo');
var app = require('express')();
var http = require('http').Server(app);
var io = require('socket.io')(http);
//var client = require('socket.io-client')('http://202.154.24.50:3000');
var request = require('request');
var ping = require('ping');
var format = require('format-number');
const isReachable = require('is-reachable');
var num_format = format({integerSeparator:'.', decimal:','});

var api_local = 'http://localhost/bo_npos_f/api/';
var api_server = 'http://178.128.40.215/bo_npos_f/api/';
var lokasi = 'LK/0002';
var lokasi_pusat = 'HO';

app.get('/', function(req, res){
  res.sendFile(__dirname + '/index.html');
});

//client.on("connect", function(){
//    console.log('connected');
//});

http.listen(3000, function() {
    console.log('Listening on *:3000');
    //exec_log();
    //send_log();
    //get_log();
    //pending_print('cek', 5000);	
});

io.sockets.on('connection', function(socket) {
	console.log("from sever");
	socket.on('print', function (data) {
		print_nota(data);
    });

	socket.on('cek_callback', function (data) {
        call(data);
    });

    socket.on('disconect', function () {
        try {
            delete user_print[socket.id_user];
        } catch (e) {
            echo('User not available');
        }
    });
});

function call(data) {
    io.broadcast.emit('ok');
}

function print_nota(data) {
    data = JSON.parse(JSON.stringify(data));
    var string = data.print;
    var json = JSON.parse(JSON.stringify(string));
    var headfoot = JSON.parse(JSON.stringify(data.headfoot));
    const title = headfoot.header1+'\n';
    const header = (headfoot.header2=='-'?'':headfoot.header2)+(headfoot.header3=='-'?'':'\n'+headfoot.header3)+(headfoot.header4=='-'?'':'\n'+headfoot.header4);
    const footer = (headfoot.footer1=='-'?'':headfoot.footer1)+(headfoot.footer2=='-'?'':'\n'+headfoot.footer2)+(headfoot.footer3=='-'?'':'\n'+headfoot.footer3)+(headfoot.footer4=='-'?'':'\n'+headfoot.footer4);
	var paper = 32;

    if (data.app == 'zakat') {
        var data_print = {
            app: data.app,
            paper: paper,
            konektor: json.konektor,
            vid: json.vid,
            pid: json.pid,
            ip: json.server,
            header: header,
            title: title,
            footer: footer,
            t1: json.t1
        };
        cek_printer(data_print, cetak);
    }
}

function pending_print(data=null, timeout=10000) {
    if (data == 'cek') {
        get_data(api_local, 'pending_print', pending_print);
    } else if (data != null) {
        data = JSON.parse(data);

        var list = data['data'];

        var jml_list = list.length;
        if (jml_list > 0) {
            list.forEach(function (item, index) {
                var data_print = {app:item.app, headfoot:item.head_foot, print:JSON.stringify([JSON.parse(item.data_print)])};

                setTimeout(function () {
                    echo(index);
                    print_nota(JSON.stringify(data_print));
                    /*if (index == (jml_list-1)) {
                        get_data(api_local, 'pending_print', pending_print);
                    }*/
                }, index*5000)
            });
        }
    }/* else {
        setTimeout(function () {
            get_data(api_local, 'pending_print', pending_print)
        }, timeout);

    }*/
}

function sukses_print(data) {
    post_data(api_local, 'print_success', data)
}

/*LOG TRX*/
function get_log(log=null) {
    setTimeout(function () {
        post_data(api_server, "get_log", "lokasi="+lokasi, res_get);
    }, 5000);
}

function res_get(data) {
    post_data(api_local, "insert_log", "data="+Buffer.from(data).toString('base64'), callback_get)
}

function callback_get(data) {
    try {
        data = JSON.parse(data);
        post_data(api_server, "success_log", "status="+data.status+"&id="+data.id, get_log);
    } catch (e) {
        get_log();
    }
}

function send_log(log=null) {
    setTimeout(function () {
        post_data(api_local, "get_log", "lokasi="+lokasi, res_send);
    }, 5000);
}

function res_send(data) {
    try {
        post_data(api_server, "insert_log", "data="+Buffer.from(data).toString('base64'), callback_send)
    } catch (e) {
        echo('failed')
    }
}

function callback_send(data) {
    try {
        data = JSON.parse(data);
        post_data(api_local, "success_log", "status="+data.status+"&id="+data.id, send_log)
    } catch (e) {
        send_log();
    }
}

function exec_log(data=null) {
    setTimeout(function () {
        post_data(api_local, "exec_log", "lokasi="+lokasi, exec_log);
    }, 10000);
}

/*END LOG TRX*/

function callback_print(data) {
    echo(data);
}

function inArray(needle, haystack) {
    var length = haystack.length;
    for(var i = 0; i < length; i++) {
        if(haystack[i] == needle) return true;
        break;
    }
    return false;
}

function post_data(server_api, param="", data="", callback=null) {
    try {
        request.post({
            headers : {'content-type' : 'application/x-www-form-urlencoded'},
            url : server_api+param,
            body : data
        }, function (error, response, body) {
            if (callback != null) {
				try {
					var parsing = JSON.parse(body);
					if (typeof parsing === 'object') {
						callback(body);
					}
				} catch(e) {
					post_data(server_api, param, data, callback);
				}
            }
        });
    } catch(e) {
        post_data(server_api, param, data, callback);
    }
}

function get_data(server_api, param, callback=null) {
    request(server_api+param, function (error, response, body) {
        if (callback != null) {
            callback(body);
        }
    });
}

function cek_printer(data, callback) {
    if (data.konektor == 'usb') {
        try {
            new escpos.USB(data.vid, data.pid);

            callback(data);
        } catch (e) {
            echo(e);

            setTimeout(function () {
                cek_printer(data, callback);
            }, 5000);
        }
    } else if (data.konektor == 'lan') {
        try {
            new escpos.Network(data.ip);
            
            callback(data);
        } catch (e) {
            //cek_printer(data, callback);
        }
        /*ping.sys.probe(data.ip, function (isAlive) {
            if (isAlive) {
                callback(data);
            } else {
                echo('Printer offline');
                setTimeout(function () {
                    cek_printer(data, callback);
                }, 5000);
            }
        });*/
    }
}

// Select the adapter based on your printer type
// const device  = new escpos.USB(vid, pid);
// const device  = new escpos.Network('192.168.10.22');
// const device  = new escpos.Serial('/dev/usb/lp0');

function cetak(data) {
	var end;
    if (data.konektor == 'usb') {
        var device  = new escpos.USB(data.vid, data.pid);
        end = '\n\n';
    } else if (data.konektor == 'lan') {
        var device  = new escpos.Network(data.ip);
        end = '\n\n\n\n';
    }
	const printer = new escpos.Printer(device);
	var app  = data.app;
	var paper = data.paper;
	const baris = sprintf("%'-"+paper+"s", '');

    if (app == 'zakat') {
        device.open(function() {
            printer
                .font('B')
                .align('CT')
                .size(1, 1)
                .text(data.title)
                .size(1, 1)
                .text(data.header)
                .align('LT')
                .text(data.t1)
                .text(end)
                .cut()
                .close((err) => {
                    if (err) {
                        console.log('error');
                    } else {
                        console.log('sukses');
                        callback_print('Print Berhasil');
                    }
                });
        });
    }

}
