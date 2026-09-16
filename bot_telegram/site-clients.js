const axios = require('axios');

const SITE_DEFINITIONS = [
    {
        key: 'ua',
        label: 'Украина',
        urlEnvironment: 'SITE_UA_URL',
        tokenEnvironment: 'SITE_UA_TOKEN',
    },
    {
        key: 'branch',
        label: 'Дубай',
        urlEnvironment: 'SITE_BRANCH_URL',
        tokenEnvironment: 'SITE_BRANCH_TOKEN',
    },
];

function readSiteConfig(environment) {
    const requiredEnvironment = SITE_DEFINITIONS.flatMap((site) => [
        site.urlEnvironment,
        site.tokenEnvironment,
    ]);
    const missingEnvironment = requiredEnvironment.filter((name) => !environment[name]);

    if (missingEnvironment.length > 0) {
        throw new Error(`Missing environment variables: ${missingEnvironment.join(', ')}`);
    }

    return SITE_DEFINITIONS.map((site) => ({
        key: site.key,
        label: site.label,
        url: environment[site.urlEnvironment].replace(/\/$/, ''),
        token: environment[site.tokenEnvironment],
    }));
}

function createSiteClients(siteConfig, axiosLibrary = axios) {
    return siteConfig.map((site) => ({
        key: site.key,
        label: site.label,
        api: axiosLibrary.create({
            baseURL: site.url,
            timeout: 10000,
            headers: {
                Authorization: `Bearer ${site.token}`,
                Accept: 'application/json',
            },
        }),
    }));
}

async function runOnEverySite(sites, callback) {
    return Promise.all(sites.map(async (site) => {
        try {
            return {
                key: site.key,
                label: site.label,
                ok: true,
                data: await callback(site.api),
            };
        } catch (error) {
            return {
                key: site.key,
                label: site.label,
                ok: false,
                error,
            };
        }
    }));
}

function registerProfileEverywhere(sites, profile) {
    return runOnEverySite(sites, async (api) => {
        const response = await api.post('/api/store-user', profile);

        return response.data;
    });
}

function fetchStatusesEverywhere(sites, profile) {
    return runOnEverySite(sites, async (api) => {
        const response = await api.post('/api/store-user', profile);

        return response.data;
    });
}

module.exports = {
    createSiteClients,
    fetchStatusesEverywhere,
    readSiteConfig,
    registerProfileEverywhere,
    runOnEverySite,
};
