declare class CommentsWidget extends HTMLElement{
    constructor(appID: string, wingetID: string, settings: {})
}
declare namespace Environment{
    class Cookies{
        get(name: string): string
        set(name: string, value: string, options?: Cookies.Options): void
        del(name: string): void
    }
    namespace Cookies{
        type Options = {
            expires?: number | Date
            path?: string
            domain?: string
            secure?: boolean
        }
    }
    class HTTP{
        get(url: string): Promise<string>
        post(url: string, data:{ [x: string]: string | number }): Promise<string>
    }
    class GOGSAPI{ // Described with 74c1baf8d7 specification
        getRaw(props: { user: string, repo: string, path: string }): Promise<{
            type: GOGSAPI.type,
            code?: number,
            message?: string,
            res?: string,
        }>
        countIPsInDB(props: { user:string, repo: string, path:string }): Promise<{
            type: GOGSAPI.type,
            code?: number,
            message?: string,
            res?: number,
        }>
        IPcountBadge(props: { user: string, repo: string, path: string, color?: string, style?: string, longCache?: boolean }): Promise<{
            type: 'error',
            code: number,
            message: string,
        }>
        getIPinfo(props: { ip: string }): Promise<{
            type: GOGSAPI.type,
            code?: number,
            message?: string,
            res?: GOGSAPI.IPInfo,
        }>
        FG_getPins(props: { lang: string, term_id?: number }): Promise<{
            type: GOGSAPI.type,
            code?: number,
            message?: string,
            res?: GOGSAPI.MapPin[],
        }>
        FG_log_err(props: { stack: string }): Promise<{
            type: GOGSAPI.type,
            code?: number,
            message?: string,
        }>
    }
    namespace GOGSAPI{
        class IPInfo{
            net: string[]
            mobile: boolean
            city: string | null
            region: string | null
            district: string | null
            country: string | null
            location: {
                lat: number
                lng: number
            }
        }
        class MapPin{
            title: string
            desc: string
            link: string
            addr: string
            lat: number
            lng: number
            thumbnail: string
            pin: string
        }
        type type = 'error' | 'success'
    }
    class Console{
        log(message?: any, ...optionalParams: any[]): void
        warn(message?: any, ...optionalParams: any[]): void
        err(error: Error | string): Promise<void>
        error(error: Error | string): Promise<void>
    }
}
declare const __dirname: string
declare const __filename: string
declare function require(url: 'https://cdn.jsdelivr.net/gh/FavoriStyle/AllBoooms-APIAssets@2.0.0-RC4/comments/widget.min.js'): Promise<typeof CommentsWidget>
declare function require(url: 'env.js' | './env.js'): Promise<{
    html: HTMLHtmlElement,
    body: HTMLBodyElement,
    is(selector): boolean,
    isAll(selectors): boolean,
    isOneOf(selectors): boolean,
    $(selector): NodeListOf<HTMLElement>,
    Cookies: Environment.Cookies,
    http: Environment.HTTP,
    apiv4pjs: { [x: string]: (data: {}) => Promise<any> }, // Need to be described with specification
    _: (props: { name: string, attrs: {[x: string]: string}, html: string }) => HTMLElement,
    gogsAPI: Environment.GOGSAPI,
    console: Environment.Console,
    GET: {[x: string]: string},
}>
declare function require(url: string):Promise<any>
