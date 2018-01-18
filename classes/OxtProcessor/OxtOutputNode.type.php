<?php

interface OxtOutputNodeType {}

abstract class OxtOutputNodeTypeAbstract implements OxtOutputNodeType {}

class OxtOutputNodeTypeTag          extends OxtOutputNodeTypeAbstract {}

class OxtOutputNodeTypeString       extends OxtOutputNodeTypeAbstract {}

class OxtOutputNodeTypeNumber       extends OxtOutputNodeTypeAbstract {}

class OxtOutputNodeTypeBoolean      extends OxtOutputNodeTypeAbstract {}

class OxtOutputNodeTypeClass        extends OxtOutputNodeTypeAbstract {}

class OxtOutputNodeTypeAttribute    extends OxtOutputNodeTypeAbstract {}

class OxtOutputNodeTypeStyle        extends OxtOutputNodeTypeAbstract {}

