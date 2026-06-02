import http from '@/api/http';

export interface RegisterData {
    email: string;
    username: string;
    name_first: string;
    name_last: string;
    password: string;
    password_confirmation: string;
    recaptchaData?: string | null;
}

export default (data: RegisterData): Promise<any> => {
    return new Promise((resolve, reject) => {
        http.get('/sanctum/csrf-cookie')
            .then(() =>
                http.post('/auth/register', {
                    email: data.email,
                    username: data.username,
                    name_first: data.name_first,
                    name_last: data.name_last,
                    password: data.password,
                    password_confirmation: data.password_confirmation,
                    'g-recaptcha-response': data.recaptchaData,
                })
            )
            .then((response) => {
                resolve(response.data);
            })
            .catch(reject);
    });
};

export const verifyOtp = (email: string, otp: string, recaptchaData?: string | null): Promise<any> => {
    return new Promise((resolve, reject) => {
        http.get('/sanctum/csrf-cookie')
            .then(() =>
                http.post('/auth/register/otp', {
                    email: email,
                    otp: otp,
                    'g-recaptcha-response': recaptchaData,
                })
            )
            .then((response) => {
                resolve(response.data);
            })
            .catch(reject);
    });
};
