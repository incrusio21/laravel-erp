import { useState } from "react";
import { createBrowserRouter, createRoutesFromElements, Route, RouterProvider } from "react-router-dom";

import DocType, { loader as loaderDocType } from "./views/DocType";
import router from "./services/router"

export default function App() {
    const [template,] = useState('admin');
    const app_route = router.set_route()
    
    const route = createBrowserRouter(createRoutesFromElements(
        <Route path={erp.boot.prefix.web}>
            {Object.entries(app_route).map(([key, value], index) => 
                <Route path={key} element={<DocType />} loader={() => loaderDocType({ params : value })} key={index}>
                    <Route index element={<div>ba</div>} />
                    <Route path="new" element={<div>ca</div>} />
                    <Route path=":name" element={<div>aa</div>} />
                </Route>
            )}
        </Route>
    ));

	return (
        <RouterProvider router={route}/>
	)
}

erp.get_route = () => router.current_route;
