<?php

interface OxtAxisType {}

abstract class OxtAxisAbstract implements OxtAxisType {}

class OxtAxisAncestor           extends OxtAxisAbstract { const SPECIFIER = 'ancestor'; }
class OxtAxisAncestorOrSelf     extends OxtAxisAbstract { const SPECIFIER = 'ancestor-or-self'; }
class OxtAxisAttribute          extends OxtAxisAbstract { const SPECIFIER = 'attribute'; }
class OxtAxisChild              extends OxtAxisAbstract { const SPECIFIER = 'child'; }
class OxtAxisDescendant         extends OxtAxisAbstract { const SPECIFIER = 'descendant'; }
class OxtAxisDescendantOrSelf   extends OxtAxisAbstract { const SPECIFIER = 'descendant-or-self'; }
class OxtAxisFollowing          extends OxtAxisAbstract { const SPECIFIER = 'following'; }
class OxtAxisFollowingSibling   extends OxtAxisAbstract { const SPECIFIER = 'following-sibling'; }
class OxtAxisNamespace          extends OxtAxisAbstract { const SPECIFIER = 'namespace'; }
class OxtAxisParent             extends OxtAxisAbstract { const SPECIFIER = 'parent'; }
class OxtAxisPreceding          extends OxtAxisAbstract { const SPECIFIER = 'preceding'; }
class OxtAxisPrecedingSibling   extends OxtAxisAbstract { const SPECIFIER = 'preceding-sibling'; }
class OxtAxisSelf               extends OxtAxisAbstract { const SPECIFIER = 'self'; }

