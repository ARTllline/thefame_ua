const assert = require('node:assert/strict');
const test = require('node:test');

const {
    createSiteClients,
    fetchStatusesEverywhere,
    readSiteConfig,
    registerProfileEverywhere,
} = require('./site-clients');

test('reads both sites from environment and creates isolated clients', () => {
    const created = [];
    const fakeAxios = {
        create(config) {
            created.push(config);
            return { config };
        },
    };

    const config = readSiteConfig({
        SITE_UA_URL: 'https://ua.example/',
        SITE_UA_TOKEN: 'ua-token',
        SITE_BRANCH_URL: 'https://branch.example/',
        SITE_BRANCH_TOKEN: 'branch-token',
    });
    const clients = createSiteClients(config, fakeAxios);

    assert.deepEqual(clients.map(({ key, label }) => ({ key, label })), [
        { key: 'ua', label: 'Украина' },
        { key: 'branch', label: 'Дубай' },
    ]);
    assert.equal(created[0].baseURL, 'https://ua.example');
    assert.equal(created[0].headers.Authorization, 'Bearer ua-token');
    assert.equal(created[1].baseURL, 'https://branch.example');
    assert.equal(created[1].headers.Authorization, 'Bearer branch-token');
});

test('requires configuration for both sites', () => {
    assert.throws(
        () => readSiteConfig({ SITE_UA_URL: 'https://ua.example' }),
        /SITE_UA_TOKEN, SITE_BRANCH_URL, SITE_BRANCH_TOKEN/,
    );
});

test('registers a profile on both sites and isolates a failed host', async () => {
    const profile = { telegram_id: 123 };
    const sites = [
        {
            key: 'ua',
            label: 'Украина',
            api: {
                post: async (path, payload) => ({
                    data: { path, payload, statuses: { enabled: true } },
                }),
            },
        },
        {
            key: 'branch',
            label: 'Дубай',
            api: {
                post: async () => {
                    throw new Error('Host unavailable');
                },
            },
        },
    ];

    const results = await registerProfileEverywhere(sites, profile);

    assert.equal(results[0].ok, true);
    assert.equal(results[0].data.path, '/api/store-user');
    assert.deepEqual(results[0].data.payload, profile);
    assert.equal(results[1].ok, false);
    assert.equal(results[1].error.message, 'Host unavailable');
});

test('uses the atomic profile response to read each local subscription status', async () => {
    const calls = [];
    const sites = [{
        key: 'ua',
        label: 'Украина',
        api: {
            post: async (path, payload) => {
                calls.push(['post', path, payload]);
                return { data: { statuses: { enabled: true } } };
            },
        },
    }];

    const results = await fetchStatusesEverywhere(sites, { telegram_id: 456 });

    assert.equal(results[0].ok, true);
    assert.deepEqual(results[0].data.statuses, { enabled: true });
    assert.deepEqual(calls, [
        ['post', '/api/store-user', { telegram_id: 456 }],
    ]);
});
