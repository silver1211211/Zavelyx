const COIN_ASSETS = Object.freeze({
    BCH: '/images/crypto/bch.svg',
    BNB: '/images/crypto/bnb.svg',
    BTC: '/images/crypto/btc.svg',
    DAI: '/images/crypto/dai.svg',
    DOGE: '/images/crypto/doge.svg',
    DOGS: '/images/crypto/dogs.png',
    ETH: '/images/crypto/eth.svg',
    GRAM: '/images/crypto/gram.png',
    LTC: '/images/crypto/ltc.svg',
    NOT: '/images/crypto/not.jpg',
    POL: '/images/crypto/pol.png',
    SHIB: '/images/crypto/shib.png',
    SOL: '/images/crypto/sol.svg',
    TRX: '/images/crypto/trx.svg',
    USDC: '/images/crypto/usdc.svg',
    USDT: '/images/crypto/usdt.svg',
    XMR: '/images/crypto/xmr.svg',
    XRP: '/images/crypto/xrp.svg',
});

const symbolFrom = value => String(value ?? '').split('_')[0].trim().toUpperCase();

export function coinAsset(value) {
    return COIN_ASSETS[symbolFrom(value)] ?? '';
}

export function networkAsset(value) {
    const network = String(value?.network ?? value?.value ?? value ?? '').trim();

    if (/bitcoin\s*cash|bitcoincash|\bbch\b/i.test(network)) return COIN_ASSETS.BCH;
    if (/binance|\bbsc\b|\bbep[- ]?20\b|\bbnb\b/i.test(network)) return COIN_ASSETS.BNB;
    if (/\bbase\b/i.test(network)) return '/images/crypto/base.svg';
    if (/ethereum|\berc[- ]?20\b|\beth\b/i.test(network)) return COIN_ASSETS.ETH;
    if (/dogecoin|\bdoge\b/i.test(network)) return COIN_ASSETS.DOGE;
    if (/litecoin|\bltc\b/i.test(network)) return COIN_ASSETS.LTC;
    if (/monero|\bxmr\b/i.test(network)) return COIN_ASSETS.XMR;
    if (/polygon|\bpol\b|\bmatic\b/i.test(network)) return COIN_ASSETS.POL;
    if (/solana|\bsol\b/i.test(network)) return COIN_ASSETS.SOL;
    if (/open network|\bton\b|\bgram\b/i.test(network)) return COIN_ASSETS.GRAM;
    if (/tron|\btrc[- ]?20\b|\btrx\b/i.test(network)) return COIN_ASSETS.TRX;
    if (/\bxrpl\b|\bxrp\b/i.test(network)) return COIN_ASSETS.XRP;
    if (/bitcoin|\bbtc\b/i.test(network)) return COIN_ASSETS.BTC;

    return '';
}
