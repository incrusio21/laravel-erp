import classNames from "classnames";

import { LoadingProps } from "./interface";

export function Loading({ type = "fixed", isBlocker, className, size = "3rem" } : LoadingProps){
    return (
        <div className={classNames(className, "loading-"+type, { "blocker" : isBlocker })}>
            <div className="spinner-border text-primary" role="status" style={{width: size, height: size}}>
                <span className="sr-only">Loading ...</span>
            </div>
        </div>
    )
}