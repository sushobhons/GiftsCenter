// commonFunctions.js

// Validate Jordanian phone number starting with '07' or '7'
const isValidJordanianMobile = (phoneNumber) => /^((77)|(78)|(79))[0-9]{7}$/.test(phoneNumber);

// Validate Jordanian phone number starting with '9627'
const isValidJordanianRFPhone = (phoneNumber) => /^(9627)[0-9]{8}$/.test(phoneNumber);

// Validate Jordanian phone number starting with '07' or '00' or '+9'
const isValidDeliveryPhone = (phoneNumber) => /^((06)|(07)|(00)|(\+9))[0-9]{8,12}$/.test(phoneNumber);

// Validate mobile number
const isValidMobile = (phoneNumber) => /^[0-9]{8,15}$/.test(phoneNumber);

// Validate email address
const isValidEmail = (email) => /^([\w-\.]+)@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.)|(([\w-]+\.)+))([a-zA-Z]{2,4}|[0-9]{1,3})(\]?)$/.test(email);

// Remove backslashes from a string
const removeBackslashes = (str) => (str + '').replace(/\\(.?)/g, (match, char) => {
    switch (char) {
        case '\\':
            return '\\';
        case '0':
            return '\u0000';
        case '':
            return '';
        default:
            return char;
    }
});

// Format a number with specified decimals, decimal point, and thousands separator
const formatNumber = (number, decimals = 0, decPoint = '.', thousandsSep = ',') => {
    number = (number + '').replace(/[^0-9+\-Ee.]/g, '');
    const numericValue = !isFinite(+number) ? 0 : +number;
    const precision = !isFinite(+decimals) ? 0 : Math.abs(decimals);
    const separator = typeof thousandsSep === 'undefined' ? ',' : thousandsSep;
    const decimal = typeof decPoint === 'undefined' ? '.' : decPoint;

    const toFixedFix = (n, prec) => {
        const k = Math.pow(10, prec);
        return '' + (Math.round(n * k) / k).toFixed(prec);
    };

    let parts = (precision ? toFixedFix(numericValue, precision) : '' + Math.round(numericValue)).split('.');

    if (parts[0].length > 3) {
        parts[0] = parts[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, separator);
    }

    if ((parts[1] || '').length < precision) {
        parts[1] = parts[1] || '';
        parts[1] += new Array(precision - parts[1].length + 1).join('0');
    }

    return parts.join(decimal);
};
