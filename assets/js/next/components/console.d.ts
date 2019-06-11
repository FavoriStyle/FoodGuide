type NativeConsole = typeof window['console']

type FunctionArgs<F> = F extends (...args: infer T) => void ? T : never

type ExtConsole = {
    log: NativeConsole['log']
    warn: NativeConsole['warn']
    error: (...args: FunctionArgs<NativeConsole['error']>) => Promise<void>
}

const console: ExtConsole

export default console