type CSSStyleDeclarationProp = keyof CSSStyleDeclaration
type ExtendedStyleDeclaration = {
    [Prop in CSSStyleDeclarationProp]: CSSStyleDeclaration[Prop] extends string ? string | number : CSSStyleDeclaration[Prop]
}

export default function css(e: Element): ExtendedStyleDeclaration
