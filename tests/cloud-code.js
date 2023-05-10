/* global Parse */
Parse.Cloud.define('bar', (request) => {
    if (request.params.key2 === 'value1') {
    return 'Foo';
  } else {
    throw 'bad stuff happened';
  }
});

Parse.Cloud.define('createTestUser', async (request) => {
  const user = new Parse.User();
  user.set('username', 'harry');
  user.set('password', 'potter');
  await user.signUp();
  const loggedIn = await Parse.User.logIn('harry', 'potter');
  return loggedIn.getSessionToken();
});

Parse.Cloud.define('foo', (request) => {
    var key1 = request.params.key1;
    var key2 = request.params.key2;
    if (key1 === "value1" && key2
        && key2.length === 3 && key2[0] === 1
        && key2[1] === 2 && key2[2] === 3) {
        result = {
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
        return result;
    } else if (key1 === 'value1') {
        return { a: 2 };
    } else {
        throw 'invalid!';
    }
});

Parse.Cloud.job('CloudJob1', () => {
  return {
    status: 'cloud job completed'
  };
});

Parse.Cloud.job('CloudJob2', () => {
  return new Promise((resolve) => {
    setTimeout(function() {
      resolve({
        status: 'cloud job completed'
      })
    }, 3000);
  });
});

Parse.Cloud.job('CloudJobFailing', () => {
  throw 'cloud job failed';
});