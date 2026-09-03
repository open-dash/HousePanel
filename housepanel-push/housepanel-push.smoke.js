"use strict";
// Behavioral smoke test for the request.post callback hardening in
// housepanel-push.js updateElements(). Simulates the callback's logic with
// representative bodies to confirm:
//   1. malformed hub index (NaN) no longer throws uncaught / pushes garbage
//   2. network error path returns early with a log, no crash
//   3. non-200 status path returns early with a log, no crash
//   4. healthy path still parses, indexes hubs correctly, and pushes items

const assert = require("assert");
const fs = require("fs");
const os = require("os");
const path = require("path");

// Mirrors the callback body now in housepanel-push.js updateElements()
function makeCallback(hubs, elements, logs) {
    return function (error, response, body) {
        if ( error || !response || response.statusCode != 200 ) {
            if ( error ) { logs.push("error: " + error.message); }
            logs.push('error attempting to read hub. statusCode:' + (response ? response.statusCode : 'none'));
            return;
        }

        var newitems;
        try {
            newitems = JSON.parse(body);
        } catch (parseError) {
            logs.push('error parsing housepanel doquery response:' + parseError.message);
            return;
        }
        if ( !Array.isArray(newitems) ) {
            logs.push('housepanel doquery response is not an array; skipping.');
            return;
        }

        var rawHubnum = newitems.pop();
        var hubnum = Number(rawHubnum);
        if ( !Number.isInteger(hubnum) || hubnum < 0 || hubnum >= hubs.length ) {
            logs.push('Malformed or out-of-range hub index from housepanel doquery; skipping this response.');
            return;
        }

        var hub = hubs[hubnum];
        if ( hub && newitems.length ) {
            newitems.forEach( function(item) {
                elements.push(item);
            });
        }
    };
}

const hubs = [
    { hubId: "h-1", hubType: "ST", hubName: "Living" },
    { hubId: "h-2", hubType: "HI", hubName: "Garage" }
];

// 1. malformed index
{
    const elements = [];
    const logs = [];
    const cb = makeCallback(hubs, elements, logs);
    cb(null, { statusCode: 200 }, JSON.stringify([{ id: "t1", value: {} }, "bogus"]));
    assert.strictEqual(elements.length, 0, "malformed index must push nothing");
    assert.ok(logs[0].includes("Malformed or out-of-range hub index"), "malformed log present");
    assert.ok(!logs.some(m => m.includes("hub information")), "no hub-info error expected");
}

// 1b. JSON object instead of array (PHP error envelope)
{
    const elements = [];
    const logs = [];
    const cb = makeCallback(hubs, elements, logs);
    cb(null, { statusCode: 200 }, JSON.stringify({ error: "no hub" }));
    assert.strictEqual(elements.length, 0);
    assert.ok(logs[0].includes("not an array"), "non-array guard log present");
}

// 1e. partially numeric values must not be truncated into a valid hub index
{
    const elements = [];
    const logs = [];
    const cb = makeCallback(hubs, elements, logs);
    cb(null, { statusCode: 200 }, JSON.stringify([{ id: "t1", value: {} }, "1junk"]));
    assert.strictEqual(elements.length, 0, "partially numeric index must push nothing");
    assert.ok(logs[0].includes("Malformed or out-of-range hub index"), "partial index log present");
}

// 1c. malformed JSON body
{
    const elements = [];
    const logs = [];
    const cb = makeCallback(hubs, elements, logs);
    cb(null, { statusCode: 200 }, "<!DOCTYPE html><html>500</html>");
    assert.strictEqual(elements.length, 0);
    assert.ok(logs[0].includes("error parsing"), "parse-error log present");
}

// 1d. out-of-range but valid integer index: no crash, no items, explicit log
{
    const elements = [];
    const logs = [];
    const cb = makeCallback(hubs, elements, logs);
    cb(null, { statusCode: 200 }, JSON.stringify([{ id: "z", value: {} }, 99]));
    assert.strictEqual(elements.length, 0, "out-of-range hub must not push items");
    assert.ok(logs[0].includes("out-of-range hub index"), "out-of-range log present");
}

// 2. network error
{
    const elements = [];
    const logs = [];
    const cb = makeCallback(hubs, elements, logs);
    cb(new Error("ECONNREFUSED"), undefined, undefined);
    assert.strictEqual(elements.length, 0);
    assert.ok(logs[0].includes("ECONNREFUSED"));
    assert.ok(logs[1].includes("statusCode:none"));
}

// 3. non-200
{
    const elements = [];
    const logs = [];
    const cb = makeCallback(hubs, elements, logs);
    cb(null, { statusCode: 500 }, "oops");
    assert.strictEqual(elements.length, 0);
    assert.ok(logs[0].includes("statusCode: 500") || logs[0].includes("statusCode:500") || logs[0].includes("500"));
}

// 4. healthy
{
    const elements = [];
    const logs = [];
    const cb = makeCallback(hubs, elements, logs);
    const body = JSON.stringify([{ id: "a", value: { x: 1 } }, { id: "b", value: { y: 2 } }, 1]);
    cb(null, { statusCode: 200 }, body);
    assert.strictEqual(elements.length, 2, "both items pushed for hub #1");
    assert.deepStrictEqual(elements.map(e => e.id), ["a", "b"]);
    assert.strictEqual(logs.length, 0, "healthy path logs nothing");
}

// 6. update handler: element missing 'value' property must not crash
{
    const elements = [
        { id: '1', value: { on: false } },
        { id: '2' } // no value property
    ];
    let threw = false;
    try {
        // Mirror of the update handler logic with the guard
        var cnt = 0;
        for (var num = 0; num < elements.length; num++) {
            var entry = elements[num];
            if (entry.id == '2' &&
                'on' != 'trackData' &&
                entry.value && typeof entry.value === 'object' &&
                entry['value']['on'] != 'true') {
                cnt = cnt + 1;
                entry['value']['on'] = 'true';
            }
        }
        assert.strictEqual(cnt, 0, "element without value must not be updated");
    } catch (e) {
        threw = true;
        assert.strictEqual(e.message, undefined, "should not have thrown: " + e.message);
    }
    assert.strictEqual(threw, false, "update handler must not throw on missing value");
}

// 6b. update handler: element with null value must not crash
{
    const elements = [
        { id: '1', value: { on: false } },
        { id: '2', value: null }
    ];
    let threw = false;
    try {
        var cnt = 0;
        for (var num = 0; num < elements.length; num++) {
            var entry = elements[num];
            if (entry.id == '2' &&
                'on' != 'trackData' &&
                entry.value && typeof entry.value === 'object' &&
                entry['value']['on'] != 'true') {
                cnt = cnt + 1;
            }
        }
        assert.strictEqual(cnt, 0, "element with null value must not be updated");
    } catch (e) {
        threw = true;
    }
    assert.strictEqual(threw, false, "update handler must not throw on null value");
}

// 6c. update handler: element with string value must not crash
{
    const elements = [
        { id: '2', value: 'some-string' }
    ];
    let threw = false;
    try {
        var cnt = 0;
        for (var num = 0; num < elements.length; num++) {
            var entry = elements[num];
            if (entry.id == '2' &&
                'on' != 'trackData' &&
                entry.value && typeof entry.value === 'object' &&
                entry['value']['on'] != 'true') {
                cnt = cnt + 1;
            }
        }
        assert.strictEqual(cnt, 0, "element with non-object value must not be updated");
    } catch (e) {
        threw = true;
    }
    assert.strictEqual(threw, false, "update handler must not throw on non-object value");
}

// ---------------------------------------------------------------------------
// Push authentication tests. These call the REAL checkPushAuth/getPushToken
// exported by housepanel-push.js -- not a copy of the logic -- so that a
// regression in production auth fails this suite.
// ---------------------------------------------------------------------------

// Point the options-file lookup at a fixture we control. locateOptionsFile()
// tries "hmoptions.cfg" relative to cwd first, so chdir into a temp dir that
// holds our fixture. Must happen before requiring the module.
const tmpdir = fs.mkdtempSync(path.join(os.tmpdir(), "hp-push-auth-"));
const cfgPath = path.join(tmpdir, "hmoptions.cfg");
const origCwd = process.cwd();

// getPushToken() re-reads only when mtime changes, so step the mtime back on
// every write; otherwise same-millisecond rewrites could be missed.
let mtimeStep = 0;
function writeCfg(configObj) {
    fs.writeFileSync(cfgPath, JSON.stringify({ config: configObj }));
    mtimeStep += 2;
    const stamp = new Date(Date.now() - (mtimeStep * 1000));
    fs.utimesSync(cfgPath, stamp, stamp);
}

// start with a config that has NO pushToken, as every pre-upgrade install does
writeCfg({ port: "19234", webSocketServerPort: "1337" });
process.chdir(tmpdir);

const push = require(path.join(__dirname, "housepanel-push.js"));

// invoke the real checkPushAuth with minimal req/res doubles and report the
// status it sent (or 200 when it authorized and sent nothing)
function callAuth(authHeader) {
    let sentStatus = null;
    const req = {
        get: function (name) {
            return (String(name).toLowerCase() === "authorization") ? authHeader : undefined;
        },
        ip: "10.0.0.5"
    };
    const res = {
        status: function (code) { sentStatus = code; return res; },
        json: function () { return res; }
    };
    const allowed = push.checkPushAuth(req, res);
    return { allowed: allowed, status: allowed ? 200 : sentStatus };
}

const REAL_TOKEN = "s3cr3t-push-token";

// 7. no pushToken configured -> 503 regardless of what is sent
{
    assert.strictEqual(callAuth("Bearer anything").status, 503, "unconfigured token must 503");
    assert.strictEqual(callAuth(undefined).status, 503, "unconfigured token must 503 with no header");
    assert.strictEqual(push.getPushToken(), null, "no token should be found in a cfg without one");
}

// 7b. REGRESSION: the token is written while this process is already running.
// The Options page writes hmoptions.cfg after housepanel-push has started, and
// the "initialize" POST that used to refresh config is itself behind auth, so
// this must start working with no restart.
{
    writeCfg({ port: "19234", pushToken: REAL_TOKEN });
    const res = callAuth("Bearer " + REAL_TOKEN);
    assert.strictEqual(res.allowed, true, "token written after startup must be picked up without a restart");
    assert.strictEqual(res.status, 200, "valid token must not set an error status");
    assert.strictEqual(push.getPushToken(), REAL_TOKEN, "getPushToken must return the freshly written token");
}

// 7c. configured token, no Authorization header -> 401
{
    const res = callAuth(undefined);
    assert.strictEqual(res.allowed, false, "missing Authorization must be rejected");
    assert.strictEqual(res.status, 401, "missing Authorization must 401");
}

// 7d. configured token, malformed Authorization -> 401
{
    assert.strictEqual(callAuth("Token " + REAL_TOKEN).status, 401, "non-Bearer scheme must 401");
    assert.strictEqual(callAuth(REAL_TOKEN).status, 401, "raw token with no scheme must 401");
    assert.strictEqual(callAuth("Bearer").status, 401, "Bearer with no token must 401");
    assert.strictEqual(callAuth("Bearer ").status, 401, "Bearer with empty token must 401");
}

// 7e. configured token, wrong bearer token -> 401
{
    assert.strictEqual(callAuth("Bearer wrong-token").status, 401, "wrong bearer token must 401");
    assert.strictEqual(callAuth("Bearer " + REAL_TOKEN + "x").status, 401, "token with extra suffix must 401");
    assert.strictEqual(callAuth("Bearer " + REAL_TOKEN.slice(0, -1)).status, 401, "truncated token must 401");
}

// 7f. correct bearer token, case-insensitive scheme -> authorized
{
    assert.strictEqual(callAuth("Bearer " + REAL_TOKEN).allowed, true, "correct bearer token must authorize");
    assert.strictEqual(callAuth("bearer " + REAL_TOKEN).allowed, true, "scheme match must be case-insensitive");
}

// 7g. a rotated token in the cfg is picked up, and the old one stops working
{
    const NEW_TOKEN = "rotated-push-token-value";
    writeCfg({ port: "19234", pushToken: NEW_TOKEN });
    assert.strictEqual(callAuth("Bearer " + NEW_TOKEN).allowed, true, "rotated token must authorize");
    assert.strictEqual(callAuth("Bearer " + REAL_TOKEN).status, 401, "superseded token must 401");
}

// 7h. a corrupt cfg must fail closed rather than throw
{
    fs.writeFileSync(cfgPath, "<!DOCTYPE html>not json");
    mtimeStep += 2;
    const stamp = new Date(Date.now() - (mtimeStep * 1000));
    fs.utimesSync(cfgPath, stamp, stamp);
    assert.strictEqual(callAuth("Bearer " + REAL_TOKEN).status, 503, "unparseable cfg must fail closed with 503");
}

// ---------------------------------------------------------------------------
// 8. HTTP-level coverage of the real Express routes. Only runs when the
// dependencies are installed (npm install); the assertions above already
// exercise production checkPushAuth without them, so skipping here is not a
// silent gap in auth coverage.
// ---------------------------------------------------------------------------
function httpTests() {
    if ( !push.app ) {
        console.log("SKIP HTTP-level route tests: express/body-parser not installed (run npm install for this coverage). " +
                    "Unit-level assertions above already ran against production checkPushAuth.");
        return Promise.resolve();
    }

    const httpmod = require("http");
    // no port/webSocketServerPort here on purpose: the "initialize" case below
    // reaches updateElements(), which would otherwise bind the configured
    // ports for real and leave this test process hanging on an open handle
    writeCfg({ pushToken: REAL_TOKEN });

    return new Promise(function (resolve, reject) {
        const listener = push.app.listen(0, "127.0.0.1", function () {
            const port = listener.address().port;

            function send(method, headers, body) {
                return new Promise(function (done, fail) {
                    const req = httpmod.request({
                        host: "127.0.0.1", port: port, path: "/", method: method, headers: headers || {}
                    }, function (res) {
                        let data = "";
                        res.on("data", function (chunk) { data += chunk; });
                        res.on("end", function () { done({ status: res.statusCode, body: data }); });
                    });
                    req.on("error", fail);
                    if ( body ) { req.write(body); }
                    req.end();
                });
            }

            const asJson = { "Content-Type": "application/json" };
            const payload = JSON.stringify({ msgtype: "update", change_device: "1", change_attribute: "switch", change_value: "on" });

            Promise.resolve()
                .then(function () {
                    return send("POST", asJson, payload).then(function (res) {
                        assert.strictEqual(res.status, 401, "POST with no Authorization must 401");
                    });
                })
                .then(function () {
                    return send("POST", Object.assign({ Authorization: "Bearer wrong" }, asJson), payload).then(function (res) {
                        assert.strictEqual(res.status, 401, "POST with wrong bearer token must 401");
                    });
                })
                .then(function () {
                    return send("POST", Object.assign({ Authorization: "Bearer " + REAL_TOKEN }, asJson), payload).then(function (res) {
                        assert.strictEqual(res.status, 200, "POST with valid bearer token must be accepted");
                    });
                })
                .then(function () {
                    // a legitimate hub "initialize" post, the message that reauthorizes hubs
                    const init = JSON.stringify({ msgtype: "initialize" });
                    return send("POST", Object.assign({ Authorization: "Bearer " + REAL_TOKEN }, asJson), init).then(function (res) {
                        assert.strictEqual(res.status, 200, "authenticated hub initialize must succeed");
                    });
                })
                .then(function () {
                    return send("GET", {}).then(function (res) {
                        assert.strictEqual(res.status, 200, "GET status page must not require auth");
                        assert.ok(res.body.indexOf("housepanel-push") >= 0, "GET must return the status page");
                        assert.ok(res.body.indexOf("Client #") < 0, "GET must not disclose per-client host details");
                    });
                })
                .then(function () { listener.close(function () { resolve(); }); })
                .catch(function (err) { listener.close(function () { reject(err); }); });
        });
        listener.on("error", reject);
    });
}

httpTests()
    .then(function () {
        process.chdir(origCwd);
        fs.rmSync(tmpdir, { recursive: true, force: true });
        console.log("ALL BEHAVIORAL ASSERTIONS PASSED");
    })
    .catch(function (err) {
        process.chdir(origCwd);
        fs.rmSync(tmpdir, { recursive: true, force: true });
        console.error(err);
        process.exit(1);
    });
