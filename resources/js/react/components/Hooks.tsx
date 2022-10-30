import { useCallback, useMemo, useReducer } from "react";
import { api, useCancelToken, usePromise } from "../services/axios";

export function sleep(time : number) {
    return new Promise<void>((resolve, reject) => {
        setTimeout(() => {
            resolve();
        }, time);
    });
}

export function useQuery(query: string, params: any = {}, refresh: number = 0){
    const [reload, forceUpdate] = useReducer(x => x + 1, 0);
    
    const data = useMemo(() => 
        usePromise(api.get(query, { params }))
    ,[reload]) 

    const refetch  = useCallback(() => 
        forceUpdate()
    ,[])
    
    return [ data, refetch ] as const
}