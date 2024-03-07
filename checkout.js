const settings = window.wc.wcSettings.getSetting( 'ipaymu_data', {} );
const label = window.wp.htmlEntities.decodeEntities( settings.title ) || window.wp.i18n.__( 'iPaymu Payment Gateway', 'iPaymu Payment Gateway' );
const Content = () => {
    return window.wp.htmlEntities.decodeEntities( settings.description || 'Pembayaran melalui Virtual Account, QRIS, Alfamart/Indomaret, Direct Debit, Kartu Kredit, dan COD' );
};
const Block_Gateway = {
    name: 'ipaymu',
    label: label,
    content: Object( window.wp.element.createElement )( Content, null ),
    edit: Object( window.wp.element.createElement )( Content, null ),
    canMakePayment: () => true,
    ariaLabel: label,
    supports: {
        features: settings.supports,
    },
};
window.wc.wcBlocksRegistry.registerPaymentMethod( Block_Gateway );