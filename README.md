# component-orm

[![Current version](https://img.shields.io/packagist/v/eureka/component-orm.svg?logo=composer)](https://packagist.org/packages/eureka/component-orm)
[![Supported PHP version](https://img.shields.io/static/v1?logo=php&label=PHP&message=7.4%20-%208.2&color=777bb4)](https://packagist.org/packages/eureka/component-orm)
![CI](https://github.com/eureka-framework/component-orm/workflows/CI/badge.svg)
[![Quality Gate Status](https://sonarcloud.io/api/project_badges/measure?project=eureka-framework_component-orm&metric=alert_status)](https://sonarcloud.io/dashboard?id=eureka-framework_component-orm)
[![Coverage](https://sonarcloud.io/api/project_badges/measure?project=eureka-framework_component-orm&metric=coverage)](https://sonarcloud.io/dashboard?id=eureka-framework_component-orm)

PHP Simple ORM

## Composer
To add this ORM to your project, you can use the following command:

```bash
~/path/to/your/project/$ composer require "eureka/component-orm"
```

You can install the component (for testing) with the following command:
```bash
make install
```

## Update

You can update the component (for testing) with the following command:
```bash
make update
```

## Configuration
If you are using Symfony Dependency Injection & Yaml config, you can configure ORM with something look like this:

### Main orm.yaml config
```yaml
parameters:

    #~ Comment part
    orm.comment.author:    'Eureka Orm Generator'
    orm.comment.copyright: 'My Copyright name'

    #~ Namespace base config
    orm.base_namespace.entity:     'Application\Domain' # Base namespace for entities
    orm.base_namespace.mapper:     'Application\Domain' # Base namespace for mapper (repository interface implementation)
    orm.base_namespace.repository: 'Application\Domain' # Base namespace for repository interfaces

    #~ Path base config
    orm.base_path.entity:     '%kernel.directory.root%/src/Domain' # Base path for entities
    orm.base_path.mapper:     '%kernel.directory.root%/src/Domain' # Base path for mapper (repository interface implementation)
    orm.base_path.repository: '%kernel.directory.root%/src/Domain' # Base namespace for repository interfaces

    #~ Cache base config
    orm.cache.enabled: false                  # Define globally if cache is enable or not
    orm.cache.prefix: 'website.magiclegacy.'  # Cache prefix for application database data

    #~ Validation - /!\ Require "eureka/component-validation" when validation is enabled
    orm.validation.enabled: true              # false: no validation of value in entities setter
    orm.validation.auto: true                 # set true to generate auto validation regarding tables columns definitions

    #~ Define list of available config.
    # Alias name will be used in "joins" config part
    orm.configs:
        #~ Usage
        #alias: '%orm.config.{name}%'
        #~ Core website
        user:                 '%orm.config.user%'

        #~ Core Blog
        blog_post:     '%orm.config.blog_post%'
        blog_category: '%orm.config.blog_category%'
        blog_tag:      '%orm.config.blog_tag%'
        blog_post_tag: '%orm.config.blog_post_tag%'
```


### Example table yaml config
```yaml
# ORM Config file
parameters:

    orm.config.user:
        #~ Some meta data for phpdoc block comments
        comment:
            author:    '%orm.comment.author%'
            copyright: '%orm.comment.copyright%'

        #~ Namespace for generated files
        namespace:
            entity:     '%orm.base_namespace.entity%\User\Entity'
            mapper:     '%orm.base_namespace.mapper%\User\Infrastructure\Mapper'
            repository: '%orm.base_namespace.repository%\User\Repository'

        #~ Path for generated files
        path:
            entity:     '%orm.base_path.entity%/User/Entity'
            mapper:     '%orm.base_path.mapper%/User/Infrastructure/Mapper'
            repository: '%orm.base_path.repository%/User/Repository'
    
        #~ Cache configuration for this table 
        cache:
            enabled:    '%orm.cache.enabled%'
            prefix:     '%orm.cache.prefix%user'

        #~ Table config
        database:
            table:      'user' # Name of the table
            prefix:     'user' # Fields prefix to remove in generated method. (Field user_id will have getId() method with this example)

        class:
            classname: 'User' # Name of the class (Do not set namespace here)

        #~ List of join configuration for "eager or lazy loading"
        joins:
            UserPosts:                   # Suffix name for setter/getter (here: getAllUserPosts() / setAllUserPosts())
                eager_loading: false     # set to true to allow eager loading
                config:   'blog_post'    # config alias name (see orm.yaml > "orm.configs" part)
                relation: 'many'         # one: Return unique entity | many: return list of all found entities 
                type:     'inner'        # inner, left, right
                keys:
                    user_id: true        # key(s) for join. set "true" when column name is same in both table

            UserAddress:                 # Suffix name for setter/getter (here: getUserAddress() / setUserAddress())
                eager_loading: true      # set to true to allow eager loading
                config:   'user_address' # config alias name (see orm.yaml > "orm.configs" part)
                relation: 'one'          # one: Return unique entity | many: return list of all found entities 
                type:     'inner'        # inner, left, right
                keys:
                    user_id: user_id     # You also can mapping name (useful when name differ in both table)

        #~ Validation configuration 
        # /!\ Require "eureka/component-validation" when validation is enabled
        validation:
            enabled: '%orm.validation.enabled%'
            auto:    '%orm.validation.auto%'

            #~ Optional
            extended_validation:
                #~ Define or override validation config for some field if needed
                #~ Example
                user_name:
                    #type: string - optional when auto validation is enabled

                    #~ options values are merged with auto validation values.
                    #~ If any value is already defined with auto validation, this value override auto validation value
                    options:
                        min_length: 5

```

### Generator

 You must generate all ORM file with following command: 
```bash
~/path/to/your/project/$ bin/console Orm/Script/Generator
```

> /!\ This command require to have "eureka/component-console" installed & bin/console script in your application (with a kernel-console) 

## Entity
 Entities are "reflection" of the table data. When you retrieve data from database, each entity is representation of one
row of corresponding table.

 Entity should never been instantiated directly. To get a new entity, you should use the following mapper method:
 
```php
/** @var \Symfony\Component\DependencyInjection\Container $container */
$postRepository = $container->get('post.mapper');
/** @var \Application\Domain\Blog\Infrastructure\Mapper\PostMapper $postRepository */
$post = $postRepository->newEntity();
```

## Mapper
 When you need to retrieve data from database, you have to add a method in mapper to retrieve data, commonly prefixed
by `find` or `findAll` (according to retrieve one or many entities).

### Mapper Example
Retrieve 10 latest blog posts (as entities)
```php
<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Application\Domain\Blog\Infrastructure\Mapper;

use Application\Domain\Blog\Entity\Post;
use Application\Domain\Blog\Repository\PostRepositoryInterface;
use Eureka\Component\Orm\EntityInterface;
use Eureka\Component\Orm\Exception\EntityNotExistsException;
use Eureka\Component\Orm\Exception\InvalidQueryException;
use Eureka\Component\Orm\Exception\OrmException;
use Eureka\Component\Orm\Query\SelectBuilder;

/**
 * Mapper class for table "blog_post"
 *
 * @author Eureka Orm Generator
 */
class PostMapper extends Abstracts\AbstractPostMapper implements PostRepositoryInterface
{
    /**
     * @param int $number
     * @return Post[]
     * @throws InvalidQueryException
     * @throws OrmException
     */
    public function findLatest(int $number = 10): iterable
    {
        //~ Create new query builder (for select)
        $queryBuilder = new SelectBuilder($this);

        //~ Add some restriction
        $queryBuilder->addWhere('blog_post_status', 3);

        //~ Ordering results
        $queryBuilder->addOrder('blog_post_id', 'DESC');

        //~ Limit number of results
        $queryBuilder->setLimit($number);

        //~ select result & return result
        return $this->select($queryBuilder);
    }

    /**
     * @param int $postId
     * @return Post[]
     * @throws OrmException
     */
    public function findPostWithUser(int $postId): iterable
    {
        //~ Create new query builder (for select)
        $queryBuilder = new SelectBuilder($this);

        //~ Add some restriction
        $queryBuilder->addWhere('blog_post_id', $postId);

        //~ Use eager loading to load user attach with Post (join only on user is this example)
        return $this->selectJoin($queryBuilder, ['user']);
    }

    /**
     * @return Post|EntityInterface
     * @throws EntityNotExistsException  
     * @throws OrmException
     */
    public function findLast(): Post
    {
        //~ Create new query builder (for select)
        $queryBuilder = new SelectBuilder($this);

        //~ Add some restriction
        $queryBuilder->addWhere('blog_post_status', 3);

        //~ Ordering results
        $queryBuilder->addOrder('blog_post_id', 'DESC');

        //~ When use selectOne(), not found entity will throw an EntityNotExistsException
        return $this->selectOne($queryBuilder);
    }
}
```

## Repository
 To have repository as representation of Driven Design Domain (DDD), ORM generate Repository interfaces that should be
used to sign your methods in your application. 
 The real implementations are mapper.
 
### Repository examples

 Here, this is an example of repository interface corresponding to the previous implementation above.
 
```php
<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Application\Domain\Blog\Repository;

use Application\Domain\Blog\Entity\Post;
use Eureka\Component\Orm\EntityInterface;
use Eureka\Component\Orm\Exception\EntityNotExistsException;
use Eureka\Component\Orm\Exception\InvalidQueryException;
use Eureka\Component\Orm\Exception\OrmException;
use Eureka\Component\Orm\RepositoryInterface;

/**
 * Post repository interface.
 *
 * @author Eureka Orm Generator
 */
interface PostRepositoryInterface extends RepositoryInterface
{
    /**
     * @param int $number
     * @return Post[]
     * @throws InvalidQueryException
     * @throws OrmException
     */
    public function findLatest(int $number = 10): iterable;

    /**
     * @param int $postId
     * @return Post[]
     * @throws OrmException
     */
    public function findPostWithUser(int $postId): iterable;

    /**
     * @return Post|EntityInterface
     * @throws EntityNotExistsException  
     * @throws OrmException
     */
    public function findLast(): Post;
}
```

## Testing & CI (Continuous Integration)

You can run tests on your side with following commands:
```bash
make tests   # run tests with coverage
make testdox # run tests without coverage reports but with prettified output
```

You also can run code style check or code style fixes with following commands:
```bash
make phpcs   # run checks on check style
make phpcbf  # run check style auto fix
```

To perform a static analyze of your code (with phpstan, lvl 9 at default), you can use the following command:
```bash
make phpstan
make analyze # Same as phpstan but with CLI output as table
```

To ensure you code still compatible with current supported version and futures versions of php, you need to
run the following commands (both are required for full support):
```bash
make php74compatibility # run compatibility check on current minimal version of php we support
make php81compatibility # run compatibility check on last version of php we will support in future
```

And the last "helper" commands, you can run before commit and push is:
```bash
make ci
```
This command clean the previous reports, install component if needed and run tests (with coverage report),
check the code style and check the php compatibility check, as it would be done in our CI.

## Contributing

See the [CONTRIBUTING](CONTRIBUTING.md) file.

## License

This project is currently under The MIT License (MIT). See [LICENCE](LICENSE) file for more information.
