
import { useCallback, useEffect, useRef } from 'react';
import axios, { CancelTokenSource }  from 'axios'

export const api = axios.create({
    responseType: "json",
    headers: {
        'Content-Type': 'application/json', 
        "X-Requested-With": "XMLHttpRequest",
    }
})

export function useCancelToken(){
    const axiosSource = useRef<CancelTokenSource>();
    const cancel = axios.isCancel;
    const newCancelToken = useCallback(() => {
        axiosSource.current = axios.CancelToken.source();;
        return axiosSource.current.token;
    }, []);
    
    useEffect(() => () => {
        if (axiosSource.current) axiosSource.current.cancel();
    },[]);
    
    return { newCancelToken, cancel };
}

export function usePromise(promise: Promise<any>) {
    let status = "pending";
    let result: any;
    let suspender = promise.then(
        (r : any) => {
            status = "success";
            result = r.data;
        },
        (e : any) => {
            status = "error";
            result = e;
        }
    );
    return {
        read() {
            if (status === "pending") {
                throw suspender;
            } else if (status === "error") {
                throw result;
            } else if (status === "success") {
                return result;
            }
        }
    };
}