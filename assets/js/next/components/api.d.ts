type MethodList = {
    FG_log_err(data: { stack: string }): void
}

type API = {
    [Method in keyof MethodList]: (data: MethodList[Method] extends (data: infer Args) => any ? Args : never ) => Promise<ReturnType<MethodList[Method]>>
}

export default API
