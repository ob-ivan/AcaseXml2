<?php

interface OxtAssociativity {}

// primitive values

interface OxtAssociativityHasLeft       extends OxtAssociativity {}
interface OxtAssociativityHasRight      extends OxtAssociativity {}
interface OxtAssociativityGroupLeft     extends OxtAssociativityHasLeft {}
interface OxtAssociativityGroupRight    extends OxtAssociativityHasRight {}
interface OxtAssociativityStepRight     extends OxtAssociativityHasRight {}

// auxiliary

interface OxtAssociativityBinary        extends OxtAssociativityHasLeft, OxtAssociativityHasRight {}

// types

class OxtAssociativityBinaryGroupLeft           implements OxtAssociativityBinary, OxtAssociativityGroupLeft  {}
class OxtAssociativityBinaryGroupRight          implements OxtAssociativityBinary, OxtAssociativityGroupRight {}
class OxtAssociativityBinaryGroupNone           implements OxtAssociativityBinary {}
class OxtAssociativityBinaryGroupLeftStepRight  implements OxtAssociativityBinary, OxtAssociativityGroupLeft, OxtAssociativityStepRight {}

class OxtAssociativityUnaryFollowingGroup       implements OxtAssociativityHasLeft, OxtAssociativityGroupLeft {}
class OxtAssociativityUnaryFollowing            implements OxtAssociativityHasLeft {}

class OxtAssociativityUnaryPrecedingGroup       implements OxtAssociativityHasRight, OxtAssociativityGroupRight {}
class OxtAssociativityUnaryPreceding            implements OxtAssociativityHasRight {}
class OxtAssociativityUnaryPrecedingStep        implements OxtAssociativityHasRight, OxtAssociativityStepRight {}


// shorthand

class OxtOperatorYFX extends OxtAssociativityBinaryGroupLeft  {}
class OxtOperatorXFY extends OxtAssociativityBinaryGroupRight {}
class OxtOperatorXFX extends OxtAssociativityBinaryGroupNone {}
class OxtOperatorYFS extends OxtAssociativityBinaryGroupLeftStepRight {}

class OxtOperatorYF extends OxtAssociativityUnaryFollowingGroup {}
class OxtOperatorXF extends OxtAssociativityUnaryFollowing {}

class OxtOperatorFY extends OxtAssociativityUnaryPrecedingGroup {}
class OxtOperatorFX extends OxtAssociativityUnaryPreceding {}
class OxtOperatorFS extends OxtAssociativityUnaryPrecedingStep {}
