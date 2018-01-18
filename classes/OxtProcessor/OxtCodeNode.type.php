<?php

interface OxtCodeNodeType {}
interface OxtOperatorType extends OxtCodeNodeType {}

// Структурные ноды.

abstract class OxtCodeNodeTypeAstract implements OxtCodeNodeType {}

class OxtCodeNodeTypeVariable     extends OxtCodeNodeTypeAstract {}
class OxtCodeNodeTypeTemplate     extends OxtCodeNodeTypeAstract {}
class OxtCodeNodeTypeAttribute    extends OxtCodeNodeTypeAstract {}
class OxtCodeNodeTypeStyle        extends OxtCodeNodeTypeAstract {}
class OxtCodeNodeTypeIf           extends OxtCodeNodeTypeAstract {}
class OxtCodeNodeTypeWhile        extends OxtCodeNodeTypeAstract {}
class OxtCodeNodeTypeFor          extends OxtCodeNodeTypeAstract {}
class OxtCodeNodeTypeChoose       extends OxtCodeNodeTypeAstract {}
class OxtCodeNodeTypeWhen         extends OxtCodeNodeTypeAstract {}
class OxtCodeNodeTypeOtherwise    extends OxtCodeNodeTypeAstract {}
class OxtCodeNodeTypeString       extends OxtCodeNodeTypeAstract {}
class OxtCodeNodeTypeNumber       extends OxtCodeNodeTypeAstract {}
class OxtCodeNodeTypeExpression   extends OxtCodeNodeTypeAstract {}
class OxtCodeNodeTypeCurrentNode  extends OxtCodeNodeTypeAstract {}
class OxtCodeNodeTypeParentNode   extends OxtCodeNodeTypeAstract {}
class OxtCodeNodeTypeLocationStep extends OxtCodeNodeTypeAstract {}
class OxtCodeNodeTypeCallTemplate extends OxtCodeNodeTypeAstract {}
class OxtCodeNodeTypeCallFunction extends OxtCodeNodeTypeAstract {}
class OxtCodeNodeTypeTag          extends OxtCodeNodeTypeAstract {}

// Части выражения.

abstract class OxtOperatorTypeAbstract  implements OxtOperatorType {}

class OxtOperatorTypeLogicalOr                  extends OxtOperatorTypeAbstract {}
class OxtOperatorTypeLogicalAnd                 extends OxtOperatorTypeAbstract {}
class OxtOperatorTypeCompareNotEqual            extends OxtOperatorTypeAbstract {}
class OxtOperatorTypeCompareEqual               extends OxtOperatorTypeAbstract {}
class OxtOperatorTypeCompareLessOrEqual         extends OxtOperatorTypeAbstract {}
class OxtOperatorTypeCompareStrictlyLess        extends OxtOperatorTypeAbstract {}
class OxtOperatorTypeCompareGreaterOrEqual      extends OxtOperatorTypeAbstract {}
class OxtOperatorTypeCompareStrictlyGreater     extends OxtOperatorTypeAbstract {}
class OxtOperatorTypeArithmeticAddition         extends OxtOperatorTypeAbstract {}
class OxtOperatorTypeArithmeticSubstraction     extends OxtOperatorTypeAbstract {}
class OxtOperatorTypeArithmeticMultiplication   extends OxtOperatorTypeAbstract {}
class OxtOperatorTypeArithmeticDivision         extends OxtOperatorTypeAbstract {}
class OxtOperatorTypeArithmeticModulo           extends OxtOperatorTypeAbstract {}
class OxtOperatorTypeUnaryMinus                 extends OxtOperatorTypeAbstract {}
class OxtOperatorTypeNodeSetUnion               extends OxtOperatorTypeAbstract {}
class OxtOperatorTypeRelativeStep               extends OxtOperatorTypeAbstract {}
class OxtOperatorTypeRelativeDescendantOrSelf   extends OxtOperatorTypeAbstract {}
class OxtOperatorTypeAbsoluteStep               extends OxtOperatorTypeAbstract {}
class OxtOperatorTypeAbsoluteDescendantOrSelf   extends OxtOperatorTypeAbstract {}

class OxtOperatorTypeStep                       extends OxtOperatorTypeAbstract {}
class OxtOperatorTypePredicate                  extends OxtOperatorTypeAbstract {}
class OxtOperatorTypeNameTest                   extends OxtOperatorTypeAbstract {}
class OxtOperatorTypeNodeTypeTest               extends OxtOperatorTypeAbstract {}
class OxtOperatorTypeVariable                   extends OxtOperatorTypeAbstract {}
class OxtOperatorTypeString                     extends OxtOperatorTypeAbstract {}
class OxtOperatorTypeNumber                     extends OxtOperatorTypeAbstract {}
class OxtOperatorTypeFunction                   extends OxtOperatorTypeAbstract {}
