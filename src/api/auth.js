import { authClient, wpClient } from "@/api/http";

export const loginWithPasswordRequest = async ({ username, password }) => {
  const response = await authClient.post("/wp-json/custom-auth/v1/login", {
    username,
    password
  });
  return response.data;
};

export const requestLoginOtp = async ({ mobile }) => {
  const response = await authClient.post("/wp-json/custom-auth/v1/login/otp", {
    mobile
  });
  return response.data;
};

export const verifyLoginOtpRequest = async ({ mobile, otp }) => {
  const response = await authClient.post("/wp-json/custom-auth/v1/login/verify", {
    mobile,
    otp
  });
  return response.data;
};

export const requestRegisterOtp = async (payload) => {
  const response = await authClient.post("/wp-json/custom-auth/v1/register/otp", payload);
  return response.data;
};

export const verifyRegisterOtpRequest = async ({ mobile, otp }) => {
  const response = await authClient.post("/wp-json/custom-auth/v1/register/verify", {
    mobile,
    otp
  });
  return response.data;
};

export const validateTokenRequest = async () => {
  const response = await authClient.post("/wp-json/custom-auth/v1/token/validate");
  return response.data;
};

export const fetchCurrentUser = async () => {
  const response = await wpClient.get("/users/me", {
    params: { context: "edit" }
  });
  return response.data;
};
