<?php

interface OxtExpressionValueType {}

abstract class OxtExpressionValueTypeAbstract implements OxtExpressionValueType {}

// Примтивное значение является массивом из объектов класса DOMNode.
class OxtExpressionValueTypeNodeset extends OxtExpressionValueTypeAbstract {}

// Примтивное значение должно реализовывать OxtOutputBlockInterface.
class OxtExpressionValueTypeOutput  extends OxtExpressionValueTypeAbstract {}

class OxtExpressionValueTypeBoolean extends OxtExpressionValueTypeAbstract {}

class OxtExpressionValueTypeNumber  extends OxtExpressionValueTypeAbstract {}

class OxtExpressionValueTypeString  extends OxtExpressionValueTypeAbstract {}

