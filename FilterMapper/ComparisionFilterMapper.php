<?php
namespace EPS\JqGridBundle\FilterMapper;
class ComparisionFilterMapper extends AbstractFilterMapper
{
    /**
     * @param array $rule
     * @param string $groupOperator
     *
     * @return mixed
     */
    public function execute(array $rule, $groupOperator = 'OR')
    {
        $parameter = $rule['data'];
        $queryBuilder = $this->grid->getQueryBuilder();
        $expression = $this->grid->getQueryBuilder()->expr();

        $where = null;
        if ($this->column->getFieldName() == "kategorie") {
            $rule['op'] = 'cat';
        }

        if ($this->column->getFieldName() == "tresc") {
            $rule['op'] = 'content';
        }

        switch ($rule['op']) {
            case 'eq':
                $where = $expression->eq($this->column->getFieldIndex(), ":{$this->column->getFieldName()}");
                break;

            case 'ne':
                $where = $expression->neq($this->column->getFieldIndex(), ":{$this->column->getFieldName()}");
                break;

            case 'lt':
                $where = $expression->lt($this->column->getFieldIndex(), ":{$this->column->getFieldName()}");
                break;

            case 'le':
                $where = $expression->lte($this->column->getFieldIndex(), ":{$this->column->getFieldName()}");
                break;

            case 'gt':
                $where = $expression->gt($this->column->getFieldIndex(), ":{$this->column->getFieldName()}");
                break;

            case 'ge':
                $where = $expression->gte($this->column->getFieldIndex(), ":{$this->column->getFieldName()}");
                break;

            case 'bw':
                $where = $expression->like($this->column->getFieldIndex(), ":{$this->column->getFieldName()}");
                $parameter = $rule['data'] . '%';
                break;

            case 'bn':
                $where = $this->column->getFieldIndex() . " NOT LIKE :{$this->column->getFieldName()}";
                $parameter = $rule['data'] . '%';
                break;

            case 'nu':
                $where = $expression
                    ->orX(
                        $expression
                            ->eq($this->column->getFieldIndex(), ":{$this->column->getFieldName()}"),
                        $this->column->getFieldIndex() . ' IS NULL');

                $parameter = '';
                break;

            case 'nn':
                $where = $expression
                    ->andX(
                        $expression
                            ->neq($this->column->getFieldIndex(), ":{$this->column->getFieldName()}"),
                        $this->column->getFieldIndex() . ' IS NOT NULL');

                $parameter = '';
                break;

            case 'in':
                if (false !== strpos($rule['data'], ',')) {

                    $where = $expression->in($this->column->getFieldIndex(), ":{$this->column->getFieldName()}");
                    $parameter = explode(',', $rule['data']);

                } elseif (false !== strpos($rule['data'], '-')) {

                    $where = $expression->between($this->column->getFieldIndex(), ":start", ":end");

                    list($start, $end) = explode('-', $rule['data']);

                    $queryBuilder->setParameter('start', $start);
                    $queryBuilder->setParameter('end', $end);

                    unset($parameter);
                }
                break;

            case 'ni':
                if (false !== strpos($rule['data'], ',')) {

                    $where = $expression->notIn($this->column->getFieldIndex(), ":{$this->column->getFieldName()}");
                    $parameter = explode(',', $rule['data']);

                } elseif (false !== strpos($rule['data'], '-')) {

                    $where = $expression->orX($this->column->getFieldIndex() . "< :start", $this->column->getFieldIndex() . "> :end");
                    list($start, $end) = explode('-', $rule['data']);
                    $queryBuilder->setParameter('start', $start);
                    $queryBuilder->setParameter('end', $end);
                    unset($parameter);
                }

                break;

            case 'ew':
                $where = $expression->like($this->column->getFieldIndex(), ":{$this->column->getFieldName()}");
                $parameter = '%' . $rule['data'];
                break;

            case 'en':
                $where = $this->column->getFieldIndex() . " NOT LIKE :{$this->column->getFieldName()}";
                $parameter = '%' . $rule['data'];
                break;

            case 'nc':
                $where = $this->column->getFieldIndex() . " NOT LIKE :{$this->column->getFieldName()}";
                $parameter = '%' . $rule['data'] . '%';
                break;

            case 'cat':
                $em = $this->grid->getQueryBuilder()->getEntityManager();
                $in = $em->getRepository('EffectSzkoleniaBundle:Wykladowca')
                    ->createQueryBuilder('ms')
                    ->select('ms.id')
                    ->join('ms.kategoriaProgramu', 'kp')
                    ->where($expression->eq('kp.nazwa', ":kategorie"));

                $where = $expression->in('u.id', $in->getDQL());
                break;

            case 'content':
                $em = $this->grid->getQueryBuilder()->getEntityManager();
                $in = $em->getRepository('EffectSzkoleniaBundle:Program')
                    ->createQueryBuilder('pt')
                    ->select('identity(pt.wykladowca)')
                    ->where('pt.programTresc LIKE :tresc');

                $parameter = '%' . $rule['data'] . '%';

                $where = $expression->in('u.id', $in->getDQL());
                break;

            default: // Case 'cn' (contains)
                $where = $expression->like($this->column->getFieldIndex(), ":{$this->column->getFieldName()}");
                $parameter = '%' . $rule['data'] . '%';
        }

        if ('OR' == $groupOperator) {
            $queryBuilder->orWhere($where);
        } else {
            $queryBuilder->andWhere($where);
        }

        if (isset($parameter)) {
            $queryBuilder->setParameter($this->column->getFieldName(), $parameter);
        }


    }
}
