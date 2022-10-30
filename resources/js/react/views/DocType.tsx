import { Suspense, useEffect } from "react";
import { Await, defer, Outlet, useAsyncError, useLoaderData, useRevalidator } from "react-router-dom";

import { Loading } from "../components/index";
import { api } from "../services/axios";
import router from "../services/router";


interface Props{
    docType: string
}

function ReviewsError() {
    const error : any = useAsyncError();

    return (
        <div>
            {error.message}
            <button onClick={() => window.dispatchEvent(new Event('refresh'))}>Refresh</button>
        </div>
    );
}


export default function Master() {    
    const loader : any = useLoaderData();
    let revalidator = useRevalidator();

    useEffect(() => {
        window.addEventListener('refresh', () => revalidator.revalidate(), true)
        window.addEventListener('reload', () => revalidator.revalidate(), true)
        return () => {
            window.removeEventListener("refresh", () => revalidator.revalidate(), true);
            window.removeEventListener("reload", () => revalidator.revalidate(), true);
        }
    }, [revalidator])

    return (
        <div>
            TES
            <Suspense fallback={<Loading  />}>
                <Await
                    resolve={loader.docType}
                    errorElement={<ReviewsError />}
                    children={(resolvedReviews) => (
                        <Outlet />
                    )}
                />
            </Suspense>
        </div>
    )
}

export async function loader({ params } : any){
    let docType = api.get(erp.boot.prefix.api + '/getdoctype', { 
        params 
    }).then(result => { 
        router.current_route = [params.doctype]

        return result.data 
    }).catch(err => {
        const error = {
            title: err.status,
            message: err.message
        }

        if(err.response){
            error.title = err.response.status
            error.message = err.response.data.error
        }
        throw new Response("Bad Request", { status: 400 }); 
    })

    return defer({ docType }) 
    // return redirect("/login");
}

