<?php

namespace Vendimia\Database;

/**
 * ON DELETE/ON UPDATE constrain actions
 */
enum ConstrainAction: string
{
    case NO_ACTION = 'NO ACTION';
    case CASCADE = 'CASCADE';
    case NULL = 'SET NULL';
    case DEFAULT = 'SET DEFAULT';
}
