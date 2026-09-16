const { Telegraf, Markup } = require('telegraf');
require('dotenv').config();

const {
    createSiteClients,
    fetchStatusesEverywhere,
    readSiteConfig,
    registerProfileEverywhere,
} = require('./site-clients');

const missingEnvironment = ['BOT_TOKEN'].filter((name) => !process.env[name]);

if (missingEnvironment.length > 0) {
    throw new Error(`Missing environment variables: ${missingEnvironment.join(', ')}`);
}

const bot = new Telegraf(process.env.BOT_TOKEN);
const sites = createSiteClients(readSiteConfig(process.env));

function mainKeyboard() {
    return Markup.keyboard([
        ['Статус уведомлений'],
        ['Мой Telegram ID'],
    ]).resize();
}

function profilePayload(ctx) {
    return {
        telegram_id: ctx.from.id,
        first_name: ctx.from.first_name || null,
        last_name: ctx.from.last_name || null,
        username: ctx.from.username || null,
    };
}

function profileLabel(ctx) {
    return ctx.from.username ? `@${ctx.from.username}` : `ID ${ctx.from.id}`;
}

function isEnabled(statuses = {}) {
    return Boolean(statuses.enabled ?? statuses.ua ?? statuses.dubai);
}

function statusMessage(results) {
    const statusLines = results.map((result) => {
        if (!result.ok) {
            return `${result.label}: ⚠️ статус временно недоступен`;
        }

        return `${result.label}: ${isEnabled(result.data.statuses) ? '✅ включены' : '❌ выключены'}`;
    });

    return [
        'Уведомления:',
        ...statusLines,
        '',
        'Изменить подписку может администратор соответствующего сайта.',
    ].join('\n');
}

function logSiteErrors(context, results) {
    results
        .filter((result) => !result.ok)
        .forEach((result) => {
            console.error(context, {
                site: result.key,
                status: result.error.response?.status,
                message: result.error.response?.data?.message || result.error.message,
            });
        });
}

function successfulResults(results) {
    return results.filter((result) => result.ok);
}

async function registerProfile(ctx) {
    return registerProfileEverywhere(sites, profilePayload(ctx));
}

async function fetchStatuses(ctx) {
    return fetchStatusesEverywhere(sites, profilePayload(ctx));
}

bot.start(async (ctx) => {
    const results = await registerProfile(ctx);
    const successful = successfulResults(results);

    logSiteErrors('Telegram profile registration failed.', results);

    if (successful.length === 0) {
        await ctx.reply(
            'Не удалось подключить профиль ни к одному сайту. Попробуйте позже.',
            mainKeyboard(),
        );
        return;
    }

    const failedLabels = results
        .filter((result) => !result.ok)
        .map((result) => result.label);

    const connectionMessage = failedLabels.length === 0
        ? 'Профиль добавлен в админ-панели обоих сайтов.'
        : `Профиль пока не добавлен: ${failedLabels.join(', ')}. Повторите /start позже.`;

    await ctx.reply(
        [
            `Telegram-профиль ${profileLabel(ctx)} подключён.`,
            connectionMessage,
            'Теперь администратор может выбрать вас в настройках уведомлений каждого сайта.',
            '',
            statusMessage(results),
        ].join('\n'),
        mainKeyboard(),
    );
});

async function replyWithStatuses(ctx, errorContext) {
    const results = await fetchStatuses(ctx);

    logSiteErrors(errorContext, results);
    await ctx.reply(statusMessage(results), mainKeyboard());
}

bot.command('status', async (ctx) => {
    await replyWithStatuses(ctx, 'Telegram status request failed.');
});

bot.hears('Статус уведомлений', async (ctx) => {
    await replyWithStatuses(ctx, 'Telegram status request failed.');
});

bot.hears('Настройка региона', async (ctx) => {
    await replyWithStatuses(ctx, 'Legacy Telegram status request failed.');
});

bot.action(/toggle_(dubai|ua)/, async (ctx) => {
    await ctx.answerCbQuery('Подписками теперь управляет администратор сайта.');

    try {
        await ctx.editMessageReplyMarkup({ inline_keyboard: [] });
    } catch (error) {
        console.error('Legacy Telegram keyboard cleanup failed.', {
            message: error.message,
        });
    }

    await replyWithStatuses(ctx, 'Legacy Telegram region button handling failed.');
});

bot.command('id', (ctx) => {
    ctx.reply(`Ваш Telegram ID: ${ctx.from.id}\nПрофиль: ${profileLabel(ctx)}`, mainKeyboard());
});

bot.hears('Мой Telegram ID', (ctx) => {
    ctx.reply(`Ваш Telegram ID: ${ctx.from.id}\nПрофиль: ${profileLabel(ctx)}`, mainKeyboard());
});

bot.on('text', (ctx) => {
    ctx.reply('Используйте кнопки «Статус уведомлений» или «Мой Telegram ID».', mainKeyboard());
});

bot.catch((error, ctx) => {
    console.error('Unhandled Telegram bot error.', {
        update_id: ctx.update?.update_id,
        message: error.message,
    });
});

bot.launch()
    .then(() => console.log('Бот запущен'))
    .catch((error) => {
        console.error('Bot launch failed.', error.message);
        process.exitCode = 1;
    });

process.once('SIGINT', () => bot.stop('SIGINT'));
process.once('SIGTERM', () => bot.stop('SIGTERM'));
