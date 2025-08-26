// src/utils/csrf.ts
//import axios from 'axios';
import napi from './axiosnapi';
export const getCsrfToken = async () => {
    await napi.get('/sanctum/csrf-cookie', {
        withCredentials: true,
    });
};
