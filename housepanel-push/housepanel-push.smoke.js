"use strict";
// Behavioral smoke test for the request.post callback hardening in
// housepanel-push.js updateElements(). Simulates the callback's logic with
// representative bodies to confirm:
//   1. malformed hub index (NaN) no longer throws uncaught / pushes garbage
//   2. network error path returns early with a log, no crash
//   3. non-200 status path returns early with a log, no crash
//   4. healthy path still parses, indexes hubs correctly, and pushes items

const assert = require("assert");

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

// 5. out-of-range index: explicit skip, no crash
{
    const elements = [];
    const logs = [];
    const cb = makeCallback(hubs, elements, logs);
    const body = JSON.stringify([{ id: "z", value: {} }, 99]);
    cb(null, { statusCode: 200 }, body);
    assert.strictEqual(elements.length, 0, "out-of-range hub must not push items");
    assert.ok(logs[0].includes("out-of-range hub index"), "out-of-range log present");
}

console.log("ALL BEHAVIORAL ASSERTIONS PASSED");
