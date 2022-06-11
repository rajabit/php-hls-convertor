let axios = require('axios');

export default axios.create({
    baseURL: '/api/',
    headers: {
        common: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
        }
    }
});