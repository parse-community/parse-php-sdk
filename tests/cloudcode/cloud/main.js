Parse.Cloud.define("bar", function(request, response) {
    if (request.params.key2 === "value1") {
        response.success('Foo');
    } else {
        response.error("bad stuff happened");
    }
});

Parse.Cloud.define("foo", function(request, response) {
    var key1 = request.params.key1;
    var key2 = request.params.key2;
    if (key1 === "value1" && key2
        && key2.length === 3 && key2[0] === 1
        && key2[1] === 2 && key2[2] === 3) {
        const result = {
            object: {
                __type: 'Object',
                className: 'Foo',
                objectId: '1',
                x: 2,
                relation: {
                    __type: 'Object',
                    className: 'Bar',
                    objectId: '2',
                    x: 3
                }
            },
            array:[
                {
                    __type: 'Object',
                    className: 'Bar',
                    objectId: '10',
                    x: 2
                }
            ]
        };
        response.success(result);
    } else if (key1 === "value1") {
        response.success({a: 2});
    } else {
        response.error('invalid!');
    }
});

Parse.Cloud.job('CloudJob1', function(request, response) {
  response.success({
    status: 'cloud job completed'
  });
});

Parse.Cloud.job('CloudJob2', function(request, response) {
  setTimeout(function() {
    response.success({
      status: 'cloud job completed'
    })
  }, 3000);
});

Parse.Cloud.job('CloudJobFailing', function(request, response) {
  response.error('cloud job failed');
});
