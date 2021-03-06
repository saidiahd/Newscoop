<?php
/**
 * @package Newscoop
 * @copyright 2011 Sourcefabric o.p.s.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

namespace Newscoop\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr;
use Newscoop\Entity\User;

/**
 * User repository
 */
class UserRepository extends EntityRepository
{
    /** @var array */
    private $setters = array(
        'username' => 'setUsername',
        'password' => 'setPassword',
        'first_name' => 'setFirstName',
        'last_name' => 'setLastName',
        'email' => 'setEmail',
        'status' => 'setStatus',
        'is_admin' => 'setAdmin',
        'is_public' => 'setPublic',
        'image' => 'setImage',
    );

    /**
     * Save user
     *
     * @param Newscoop\Entity\User $user
     * @param array $values
     * @return void
     */
    public function save(User $user, array $values)
    {
        $this->setProperties($user, $values);

        if (!$user->getUsername()) {
            throw new \InvalidArgumentException('username_empty');
        }

        if (!$this->isUnique('username', $user->getUsername(), $user->getId())) {
            throw new \InvalidArgumentException('username_conflict');
        }

        if (!$user->getEmail()) {
            throw new \InvalidArgumentException('email_empty');
        }

        if (!$this->isUnique('email', $user->getEmail(), $user->getId())) {
            throw new \InvalidArgumentException('email_conflict');
        }

        if (array_key_exists('attributes', $values)) {
            $this->setAttributes($user, (array) $values['attributes']);
        }

        if (array_key_exists('user_type', $values)) {
            $this->setUserTypes($user, (array) $values['user_type']);
        }

        if (array_key_exists('author', $values)) {
            $author = null;
            if (!empty($values['author'])) {
                $author = $this->getEntityManager()->getReference('Newscoop\Entity\Author', $values['author']);
            }
            $user->setAuthor($author);
        }

        $this->getEntityManager()->persist($user);
    }

    /**
     * Set user properties
     *
     * @param Newscoop\Entity\User $user
     * @param array $values
     * @return void
     */
    private function setProperties(User $user, array $values)
    {
        foreach ($this->setters as $property => $setter) {
            if (array_key_exists($property, $values)) {
                $user->$setter($values[$property]);
            }
        }
    }

    /**
     * Set user attributes
     *
     * @param Newscoop\Entity\User $user
     * @param array $attributes
     * @return void
     */
    private function setAttributes(User $user, array $attributes)
    {
        if (!$user->getId()) { // must persist user before adding attributes
            $this->getEntityManager()->persist($user);
            $this->getEntityManager()->flush();
        }

        foreach ($attributes as $name => $value) {
            $user->addAttribute($name, $value);
        }
    }

    /**
     * Set user types
     *
     * @param Newscoop\Entity\User $user
     * @param array $types
     * @return void
     */
    private function setUserTypes(User $user, array $types)
    {
        $user->getUserTypes()->clear();
        foreach ($types as $type) {
            $user->addUserType($this->getEntityManager()->getReference('Newscoop\Entity\User\Group', $type));
        }
    }

    /**
     * Test if property value is unique
     *
     * @param string $property
     * @param string $value
     * @param int $id
     * @return bool
     */
    public function isUnique($property, $value, $id = null)
    {
        $qb = $this->getEntityManager()->createQueryBuilder()
            ->select('COUNT(u.id)')
            ->from('Newscoop\Entity\User', 'u')
            ->where("LOWER(u.{$property}) = LOWER(?0)");

        $params = array($value);

        if ($id !== null) {
            $qb->andWhere('u.id <> ?1');
            $params[] = $id;
        }

        $qb->setParameters($params);

        return !$qb->getQuery()->getSingleScalarResult();
    }

    public function getActiveUsers()
    {
        $em = $this->getEntityManager();

        $queryBuilder = $em->getRepository('Newscoop\Entity\User')
            ->createQueryBuilder('u')
            ->where('u.status = :status')
            ->andWhere('u.is_public = :public')
            ->setParameters(array(
                'status' => User::STATUS_ACTIVE,
                'public' => true
            ));

        $query = $queryBuilder->getQuery();

        return $query;
    }

    public function getOneActiveUser($id)
    {
        $em = $this->getEntityManager();

        $queryBuilder = $em->getRepository('Newscoop\Entity\User')
            ->createQueryBuilder('u')
            ->where('u.status = :status')
            ->andWhere('u.is_public = :public')
            ->andWhere('u.id = :id')
            ->setParameters(array(
                'status' => User::STATUS_ACTIVE,
                'public' => true,
                'id' => $id
            ));

        $query = $queryBuilder->getQuery();

        return $query;
    }

    public function findActiveUsers($countOnly, $offset, $limit)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        if ($countOnly) {
            $qb->select('COUNT(u.id)');
        }
        else {
            $qb->select('u');
        }

        $qb->from('Newscoop\Entity\User', 'u');

        $qb->where($qb->expr()->eq("u.status", User::STATUS_ACTIVE));
        $qb->andWhere($qb->expr()->eq("u.is_public", true));

        if ($countOnly === false) {
            $qb->orderBy('u.points', 'DESC');
            $qb->addOrderBy('u.id', 'ASC');

            $qb->setFirstResult($offset);
            $qb->setMaxResults($limit);

            return $qb->getQuery()->getResult();
        }
        else {
            return $qb->getQuery()->getOneOrNullResult();
        }
    }

    /**
     * Return Users if their last name begins with one of the letter passed in.
     *
     * @param array $letters = ['a', 'b']
     *
     * @return array Newscoop\Entity\User
     */
    public function findUsersLastNameInRange($letters, $countOnly, $offset, $limit)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        if ($countOnly) {
            $qb->select('COUNT(u.id)');
        }
        else {
            $qb->select('u');
        }

        $qb->from('Newscoop\Entity\User', 'u');

        $qb->where($qb->expr()->eq("u.status", User::STATUS_ACTIVE));
        $qb->andWhere($qb->expr()->eq("u.is_public", true));

        $letterIndex = $qb->expr()->orx();
        for ($i=0; $i < count($letters); $i++) {
            $letterIndex->add($qb->expr()->like("LOWER(u.username)", "'$letters[$i]%'"));
        }
        $qb->andWhere($letterIndex);

        if ($countOnly === false) {
            $qb->orderBy('u.username', 'ASC');
            $qb->addOrderBy('u.id', 'ASC');

            $qb->setFirstResult($offset);
            $qb->setMaxResults($limit);

            return $qb->getQuery()->getResult();
        }
        else {
            return $qb->getQuery()->getOneOrNullResult();
        }
    }

    /**
     * Return Users if any of their searched attributes contain the searched term.
     *
     * @param string $search
     *
     * @param array $attributes
     *
     * @return array Newscoop\Entity\User
     */
    public function searchUsers($search, $countOnly, $offset, $limit, $attributes = array("first_name", "last_name", "username"))
    {
        $keywords = explode(" ", $search);

        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('u')
            ->from('Newscoop\Entity\User', 'u');

        $outerAnd = $qb->expr()->andx();

        for($i=0; $i < count($keywords); $i++) {
            $innerOr = $qb->expr()->orx();
            for ($j=0; $j < count($attributes); $j++) {
                $innerOr->add($qb->expr()->like("u.{$attributes[$j]}", "'$keywords[$i]'"));
            }
            $outerAnd->add($innerOr);
        }

        $outerAnd->add($qb->expr()->eq("u.status", User::STATUS_ACTIVE));
        $outerAnd->add($qb->expr()->eq("u.is_public", true));

        $qb->where($outerAnd);

        $qb->orderBy('u.last_name', 'ASC');
        $qb->addOrderBy('u.first_name', 'ASC');
        $qb->addOrderBy('u.id', 'DESC');

        $qb->setFirstResult($offset);
        $qb->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    /**
     * Get random list of users
     *
     * @param int $limit
     * @return array
     */
    public function getRandomList($limit)
    {
        $query = $this->getEntityManager()->createQuery("SELECT u, RAND() as random FROM {$this->getEntityName()} u WHERE u.status = :status AND u.is_public = :public ORDER BY random");
        $query->setMaxResults($limit);
        $query->setParameters(array(
            'status' => User::STATUS_ACTIVE,
            'public' => True,
        ));

        $users = array();
        foreach ($query->getResult() as $result) {
            $users[] = $result[0];
        }

        return $users;
    }

    /**
     * Get editors
     *
     * @param int $blogRole
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function findEditors($blogRole, $limit, $offset)
    {
        $query = $this->createQueryBuilder('u')
            ->leftJoin('u.groups', 'g', Expr\Join::WITH, 'g.id = ' . $blogRole)
            ->where('u.is_admin = :admin')
            ->andWhere('u.status = :status')
            ->andWhere('u.author IS NOT NULL')
            ->andWhere('g.id IS NULL')
            ->orderBy('u.username', 'asc')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery();

        $query->setParameters(array(
            'admin' => 1,
            'status' => User::STATUS_ACTIVE,
        ));

        return $query->getResult();
    }

    /**
     * Get editors count
     *
     * @param int $blogRole
     * @return int
     */
    public function getEditorsCount($blogRole)
    {
        $query = $this->getEntityManager()
            ->createQueryBuilder()
            ->select('COUNT(u)')
            ->from($this->getEntityName(), 'u')
            ->leftJoin('u.groups', 'g', Expr\Join::WITH, 'g.id = ' . $blogRole)
            ->where('u.is_admin = :admin')
            ->andWhere('u.status = :status')
            ->andWhere('u.author IS NOT NULL')
            ->andWhere('g.id IS NULL')
            ->getQuery();

        $query->setParameters(array(
            'admin' => 1,
            'status' => User::STATUS_ACTIVE,
        ));

        return $query->getSingleScalarResult();
    }

    /**
     * Get total users count
     *
     * @return int
     */
    public function countAll()
    {
        $query = $this->getEntityManager()->createQueryBuilder()
            ->select('COUNT(u)')
            ->from($this->getEntityName(), 'u')
            ->getQuery();

        return (int) $query->getSingleScalarResult();
    }

    /**
     * Get users count for given criteria
     *
     * @param array $criteria
     * @return int
     */
    public function countBy(array $criteria)
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder()
            ->select('COUNT(u)')
            ->from($this->getEntityName(), 'u');

        foreach ($criteria as $property => $value) {
            if (!is_array($value)) {
                $queryBuilder->andWhere("u.$property = :$property");
            }
        }

        $query = $queryBuilder->getQuery();
        foreach ($criteria as $property => $value) {
            if (!is_array($value)) {
                $query->setParameter($property, $value);
            }
        }

        return (int) $query->getSingleScalarResult();
    }

    /**
     * Delete user
     *
     * @param Newscoop\Entity\User $user
     * @return void
     */
    public function delete(User $user)
    {
        if ($user->isPending()) {
            $this->getEntityManager()->remove($user);
        } else {
            $user->setStatus(User::STATUS_DELETED);
            $user->setEmail(null);
            $user->setFirstName(null);
            $user->setLastName(null);
            $this->removeAttributes($user);
        }

        $this->getEntityManager()->flush();
    }

    /**
     * Remove user attributes
     *
     * @param Newscoop\Entity\User $user
     * @return void
     */
    private function removeAttributes(User $user)
    {
        $attributes = $this->getEntityManager()->getRepository('Newscoop\Entity\UserAttribute')->findBy(array(
            'user' => $user->getId(),
        ));

        foreach ($attributes as $attribute) {
            $user->addAttribute($attribute->getName(), null);
            $this->getEntityManager()->remove($attribute);
        }
    }

    /**
     * Find users for indexing
     *
     * @return array
     */
    public function getBatch()
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.indexed IS NULL OR u.indexed < u.updated')
            ->getQuery()
            ->setMaxResults(50)
            ->getResult();
    }

    /**
     * Set indexed now
     *
     * @param array $users
     * @return void
     */
    public function setIndexedNow(array $users)
    {
        if (empty($users)) {
            return;
        }

        $this->getEntityManager()->createQuery('UPDATE Newscoop\Entity\User u SET u.indexed = CURRENT_TIMESTAMP() WHERE u.id IN (:users)')
            ->setParameter('users', array_map(function($user) { return $user->getId(); }, $users))
            ->execute();
    }

    /**
     * Set indexed null
     *
     * @return void
     */
    public function setIndexedNull()
    {
        $this->getEntityManager()->createQuery('UPDATE Newscoop\Entity\User u SET u.indexed = NULL')
            ->execute();
    }

    /**
     * Get newscoop login count
     *
     * @return int
     */
    public function getNewscoopLoginCount()
    {
        $query = $this->createQueryBuilder('u')
            ->select('COUNT(u)')
            ->leftJoin('u.identities', 'ui')
            ->where('ui.user IS NULL')
            ->andWhere('u.status = :status')
            ->getQuery();

        $query->setParameter('status', User::STATUS_ACTIVE);
        return $query->getSingleScalarResult();
    }

    /**
     * Get external login count
     *
     * @return int
     */
    public function getExternalLoginCount()
    {
        $query = $this->createQueryBuilder('u')
            ->select('COUNT(u)')
            ->leftJoin('u.identities', 'ui')
            ->where('ui.user IS NOT NULL')
            ->andWhere('u.status = :status')
            ->getQuery();

        $query->setParameter('status', User::STATUS_ACTIVE);
        return $query->getSingleScalarResult();
    }

    /**
     * Get user points
     *
     * @param Newscoop\Entity\User $user
     * @return void
     */
    public function getUserPoints(User $user)
    {
        $query = $this->createQueryBuilder('u')
            ->select('u.id, ' . $this->getUserPointsSelect())
            ->where('u.id = :user')
            ->getQuery();

        $query->setParameter('user', $user->getId());
        $result = $query->getSingleResult();
        $user->setPoints($result['comments'] + $result['articles']);
    }

    /**
     * Get user points select statement
     *
     * @return string
     */
    private function getUserPointsSelect()
    {
        $commentsCount = "(SELECT COUNT(c)";
        $commentsCount .= " FROM Newscoop\Entity\Comment c, Newscoop\Entity\Comment\Commenter cc";
        $commentsCount .= " WHERE c.commenter = cc AND cc.user = u) as comments";

        $articlesCount = "(SELECT COUNT(a)";
        $articlesCount .= " FROM Newscoop\Entity\Article a, Newscoop\Entity\ArticleAuthor aa";
        $articlesCount .= " WHERE ";
        $articlesCount .= implode(' AND ', array(
            'a.number = aa.articleNumber',
            'a.language = aa.languageId',
            'aa.authorId = u.author',
            "a.type IN ('news', 'blog')",
            sprintf("a.workflowStatus = '%s'", Article::STATUS_PUBLISHED),
        ));
        $articlesCount .= ") as articles";

        return "{$commentsCount}, {$articlesCount}";
    }
}
